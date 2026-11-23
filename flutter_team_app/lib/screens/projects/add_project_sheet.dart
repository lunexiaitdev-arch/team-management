import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/projects_provider.dart';
import '../../providers/profiles_provider.dart';
import '../../providers/team_members_provider.dart';
import '../../theme/app_colors.dart';

class AddProjectSheet extends StatefulWidget {
  const AddProjectSheet({super.key});

  @override
  State<AddProjectSheet> createState() => _AddProjectSheetState();
}

class _AddProjectSheetState extends State<AddProjectSheet> {
  final _orderIdController = TextEditingController();
  final _buyerController = TextEditingController();
  final _titleController = TextEditingController();
  final _priceController = TextEditingController();
  final _notesController = TextEditingController();
  final _sheetLinkController = TextEditingController();

  DateTime _deliveryDate = DateTime.now().add(const Duration(days: 3));
  String? _selectedProfileId;
  String _selectedTeam = 'WpTitans';
  String _selectedOrderSource = 'Query';
  final List<int> _selectedAssigneeIds = [];
  String _specialistSquadFilter = 'all';
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<ProfilesProvider>(context, listen: false).fetchProfiles();
      Provider.of<TeamMembersProvider>(context, listen: false).fetchMembers();
    });
  }

  @override
  void dispose() {
    _orderIdController.dispose();
    _buyerController.dispose();
    _titleController.dispose();
    _priceController.dispose();
    _notesController.dispose();
    _sheetLinkController.dispose();
    super.dispose();
  }

  Future<void> _pickDeliveryDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _deliveryDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() => _deliveryDate = picked);
    }
  }

  Future<void> _handleSubmit() async {
    final orderId = _orderIdController.text.trim();
    final buyer = _buyerController.text.trim();
    final title = _titleController.text.trim();
    final priceStr = _priceController.text.trim();

    if (orderId.isEmpty || buyer.isEmpty || title.isEmpty || priceStr.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please fill all required fields.')),
      );
      return;
    }

    final double? price = double.tryParse(priceStr);
    if (price == null || price <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a valid order price.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final payload = {
      'order_id': orderId.startsWith('#') ? orderId : '#$orderId',
      'buyer_username': buyer,
      'client_name': buyer,
      'title': title,
      'order_value': price,
      'gross_order_value': price,
      'delivery_date': DateFormat('yyyy-MM-dd 23:59:59').format(_deliveryDate),
      'team': _selectedTeam,
      'team_name': _selectedTeam,
      'order_source': _selectedOrderSource,
      'project_sheet_link': _sheetLinkController.text.trim(),
      'fiverr_profile_id': _selectedProfileId != null ? int.tryParse(_selectedProfileId!) : null,
      'assigned_user_ids': _selectedAssigneeIds,
      'assignees': _selectedAssigneeIds.map((uid) {
        final split = _selectedAssigneeIds.isNotEmpty ? (100.0 / _selectedAssigneeIds.length) : 100.0;
        return {
          'user_id': uid,
          'split_percent': split,
        };
      }).toList(),
      'notes': _notesController.text.trim(),
      'project_notes': _notesController.text.trim(),
    };

    final prov = Provider.of<ProjectsProvider>(context, listen: false);
    final success = await prov.addProject(payload);

    if (mounted) {
      setState(() => _isSubmitting = false);
      if (success) {
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Order added to pipeline successfully!'), backgroundColor: AppColors.success),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to add order. Please check inputs.'), backgroundColor: AppColors.danger),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final profilesProv = Provider.of<ProfilesProvider>(context);
    final membersProv = Provider.of<TeamMembersProvider>(context);

    return DraggableScrollableSheet(
      initialChildSize: 0.9,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
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
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Add New Project Order',
                      style: TextStyle(
                        color: isDark ? AppColors.textLight : AppColors.textDark,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),

              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(20),
                  children: [
                    // Order ID & Price Row
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildFieldLabel('ORDER ID *', isDark),
                              const SizedBox(height: 6),
                              TextField(
                                controller: _orderIdController,
                                style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
                                decoration: const InputDecoration(hintText: 'e.g. FO4298104'),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildFieldLabel('ORDER VALUE (\$) *', isDark),
                              const SizedBox(height: 6),
                              TextField(
                                controller: _priceController,
                                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
                                decoration: const InputDecoration(hintText: 'e.g. 150'),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Fiverr Account Dropdown
                    _buildFieldLabel('FIVERR PROFILE', isDark),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: _selectedProfileId,
                      dropdownColor: isDark ? AppColors.darkCard : AppColors.lightCard,
                      style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
                      decoration: const InputDecoration(hintText: 'Select Fiverr Account'),
                      items: profilesProv.profiles.map((p) {
                        return DropdownMenuItem(
                          value: p.id.toString(),
                          child: Text(p.name),
                        );
                      }).toList(),
                      onChanged: (val) => setState(() => _selectedProfileId = val),
                    ),
                    const SizedBox(height: 16),

                    // Client Buyer Username
                    _buildFieldLabel('BUYER USERNAME *', isDark),
                    const SizedBox(height: 6),
                    TextField(
                      controller: _buyerController,
                      style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
                      decoration: const InputDecoration(hintText: 'e.g. john_doe_agency'),
                    ),
                    const SizedBox(height: 16),

                    // Project Title
                    _buildFieldLabel('PROJECT TITLE *', isDark),
                    const SizedBox(height: 6),
                    TextField(
                      controller: _titleController,
                      style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
                      decoration: const InputDecoration(hintText: 'e.g. Elementor Pro Redesign & Speed Optimization'),
                    ),
                    const SizedBox(height: 16),

                    // Google Sheet Link / Attachment
                    _buildFieldLabel('GOOGLE SHEET LINK / ATTACHMENT', isDark),
                    const SizedBox(height: 6),
                    TextField(
                      controller: _sheetLinkController,
                      style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
                      decoration: const InputDecoration(
                        hintText: 'https://docs.google.com/spreadsheets/d/... or sheet details',
                        prefixIcon: Icon(Icons.table_chart_rounded, size: 18),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Order Source (Repeat Customer)
                    _buildFieldLabel('ORDER SOURCE (REPEAT CLIENT) *', isDark),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: _selectedOrderSource,
                      dropdownColor: isDark ? AppColors.darkCard : AppColors.lightCard,
                      style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
                      decoration: const InputDecoration(hintText: 'Select Order Source'),
                      items: const [
                        DropdownMenuItem(value: 'Query', child: Text('Query (New Client)')),
                        DropdownMenuItem(value: 'Repeat Client', child: Text('🔁 Repeat Client (Existing Customer)')),
                        DropdownMenuItem(value: 'Custom Order Request', child: Text('Custom Order Request')),
                        DropdownMenuItem(value: 'Follow Up', child: Text('Follow Up')),
                        DropdownMenuItem(value: 'Brief', child: Text('Brief')),
                        DropdownMenuItem(value: 'Add On', child: Text('Add On')),
                        DropdownMenuItem(value: 'TIP', child: Text('TIP')),
                        DropdownMenuItem(value: 'Direct Purchase', child: Text('Direct Purchase')),
                      ],
                      onChanged: (val) {
                        if (val != null) setState(() => _selectedOrderSource = val);
                      },
                    ),
                    const SizedBox(height: 16),

                    // Delivery Date Picker
                    _buildFieldLabel('DELIVERY DEADLINE *', isDark),
                    const SizedBox(height: 6),
                    InkWell(
                      onTap: _pickDeliveryDate,
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
                            Text(
                              DateFormat('EEEE, dd MMM yyyy').format(_deliveryDate),
                              style: TextStyle(
                                color: isDark ? AppColors.textLight : AppColors.textDark,
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const Icon(Icons.calendar_today_rounded, size: 16, color: AppColors.primaryBlueLight),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Squad / Team Selector
                    _buildFieldLabel('ASSIGNED SQUAD', isDark),
                    const SizedBox(height: 6),
                    Wrap(
                      spacing: 8,
                      children: ['WpTitans', 'WpNexus', 'WpCraft', 'WpHorizon'].map((team) {
                        final isSelected = _selectedTeam == team;
                        return ChoiceChip(
                          label: Text(team),
                          selected: isSelected,
                          selectedColor: AppColors.primaryBlue,
                          labelStyle: TextStyle(
                            color: isSelected ? Colors.white : (isDark ? AppColors.textLight : AppColors.textDark),
                            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                            fontSize: 12,
                          ),
                          onSelected: (_) => setState(() => _selectedTeam = team),
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 16),

                    // Specialist Assignees Multi-select (with Cross-Team Squad Filtering)
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        _buildFieldLabel('SELECT SPECIALISTS', isDark),
                        if (_selectedAssigneeIds.isNotEmpty)
                          Text(
                            '${_selectedAssigneeIds.length} selected',
                            style: const TextStyle(
                              color: AppColors.primaryBlueLight,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    // Squad Filter Tabs
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _buildFilterTab('all', 'All Specialists', isDark),
                          const SizedBox(width: 6),
                          _buildFilterTab('squad', '★ $_selectedTeam', isDark),
                          const SizedBox(width: 6),
                          _buildFilterTab('WpTitans', 'WpTitans', isDark),
                          const SizedBox(width: 6),
                          _buildFilterTab('WpCraft', 'WpCraft', isDark),
                          const SizedBox(width: 6),
                          _buildFilterTab('WpNexus', 'WpNexus', isDark),
                          if (_selectedAssigneeIds.isNotEmpty) ...[
                            const SizedBox(width: 6),
                            _buildFilterTab('selected', '✓ Assigned (${_selectedAssigneeIds.length})', isDark),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 10),
                    Builder(
                      builder: (_) {
                        final displayed = membersProv.members.where((m) {
                          if (_specialistSquadFilter == 'selected') {
                            return _selectedAssigneeIds.contains(m.id);
                          } else if (_specialistSquadFilter == 'squad') {
                            return m.team.toLowerCase().replaceAll(' ', '') == _selectedTeam.toLowerCase().replaceAll(' ', '');
                          } else if (_specialistSquadFilter != 'all') {
                            return m.team.toLowerCase().replaceAll(' ', '') == _specialistSquadFilter.toLowerCase().replaceAll(' ', '');
                          }
                          return true;
                        }).toList();

                        if (displayed.isEmpty) {
                          return Padding(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            child: Text(
                              'No specialists match this squad filter.',
                              style: TextStyle(
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                fontSize: 12,
                              ),
                            ),
                          );
                        }

                        return Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: displayed.map((m) {
                            final isAssigned = _selectedAssigneeIds.contains(m.id);
                            final isCrossTeam = m.team.isNotEmpty &&
                                m.team.toLowerCase().replaceAll(' ', '') != _selectedTeam.toLowerCase().replaceAll(' ', '');
                            return FilterChip(
                              avatar: CircleAvatar(
                                radius: 10,
                                backgroundColor: isAssigned ? AppColors.primaryBlueLight : AppColors.darkSurface,
                                child: Text(
                                  m.name.isNotEmpty ? m.name[0].toUpperCase() : 'S',
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: isAssigned ? Colors.white : AppColors.textDim,
                                  ),
                                ),
                              ),
                              label: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(m.name),
                                  if (isCrossTeam) ...[
                                    const SizedBox(width: 4),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                                      decoration: BoxDecoration(
                                        color: AppColors.primaryBlue.withOpacity(0.18),
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(
                                        m.team,
                                        style: const TextStyle(
                                          fontSize: 9,
                                          fontWeight: FontWeight.w700,
                                          color: AppColors.primaryBlueLight,
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                              selected: isAssigned,
                              selectedColor: AppColors.primaryBlue.withOpacity(0.2),
                              checkmarkColor: AppColors.primaryBlueLight,
                              labelStyle: TextStyle(
                                color: isAssigned
                                    ? AppColors.primaryBlueLight
                                    : (isDark ? AppColors.textLight : AppColors.textDark),
                                fontSize: 12,
                                fontWeight: isAssigned ? FontWeight.w700 : FontWeight.w500,
                              ),
                              onSelected: (selected) {
                                setState(() {
                                  if (selected) {
                                    _selectedAssigneeIds.add(m.id);
                                  } else {
                                    _selectedAssigneeIds.remove(m.id);
                                  }
                                });
                              },
                            );
                          }).toList(),
                        );
                      },
                    ),
                    const SizedBox(height: 16),

                    // Notes
                    _buildFieldLabel('INITIAL NOTES & BRIEF', isDark),
                    const SizedBox(height: 6),
                    TextField(
                      controller: _notesController,
                      maxLines: 2,
                      style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
                      decoration: const InputDecoration(hintText: 'Briefing details, client Figma URL, staging logins...'),
                    ),
                    const SizedBox(height: 24),

                    // Submit Button
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        onPressed: _isSubmitting ? null : _handleSubmit,
                        child: _isSubmitting
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : const Text('Add Order to Pipeline', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildFieldLabel(String label, bool isDark) {
    return Text(
      label,
      style: TextStyle(
        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
        fontSize: 11,
        fontWeight: FontWeight.w700,
        letterSpacing: 0.5,
      ),
    );
  }

  Widget _buildFilterTab(String key, String label, bool isDark) {
    final isSelected = _specialistSquadFilter == key;
    return InkWell(
      onTap: () => setState(() => _specialistSquadFilter = key),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.primaryBlue
              : (isDark ? AppColors.darkCard : AppColors.lightCard),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected
                ? AppColors.primaryBlue
                : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected
                ? Colors.white
                : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
          ),
        ),
      ),
    );
  }
}
