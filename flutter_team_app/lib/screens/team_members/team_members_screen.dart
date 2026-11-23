import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/team_members_provider.dart';
import '../../providers/auth_provider.dart';
import '../../models/user.dart';
import '../../theme/app_colors.dart';
import '../../widgets/team_badge.dart';
import '../../widgets/filter_pill_group.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/drawer_opener.dart';
import 'member_profile_screen.dart';

class TeamMembersScreen extends StatefulWidget {
  const TeamMembersScreen({super.key});

  @override
  State<TeamMembersScreen> createState() => _TeamMembersScreenState();
}

class _TeamMembersScreenState extends State<TeamMembersScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<TeamMembersProvider>(context, listen: false).fetchMembers();
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<TeamMembersProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    final canManageMembers = auth.isOperationsManager;
    final filtered = prov.filteredMembers;

    final teamPills = [
      FilterPillItem(keyName: 'all', label: 'All Specialists', count: prov.members.length),
      FilterPillItem(keyName: 'WpTitans', label: 'WpTitans'),
      FilterPillItem(keyName: 'WpNexus', label: 'WpNexus'),
      FilterPillItem(keyName: 'WpCraft', label: 'WpCraft'),
      FilterPillItem(keyName: 'WpHorizon', label: 'WpHorizon'),
      FilterPillItem(keyName: 'Management', label: 'Management'),
    ];

    return Scaffold(
      appBar: AppBar(
        leading: Builder(
          builder: (ctx) => IconButton(
            icon: const Icon(Icons.menu_rounded),
            onPressed: () {
              final opener = DrawerOpener.of(ctx);
              if (opener != null) {
                opener.openDrawer();
              } else {
                Scaffold.of(ctx).openDrawer();
              }
            },
            tooltip: 'Open Menu',
          ),
        ),
        title: const Text('Agency Specialists'),
        actions: [
          if (canManageMembers)
            IconButton(
              icon: const Icon(Icons.person_add_alt_1_rounded),
              onPressed: () => _showManageMemberSheet(context, prov, null),
              tooltip: 'Add Member',
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => prov.fetchMembers(),
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: Column(
        children: [
          const SizedBox(height: 8),
          FilterPillGroup(
            items: teamPills,
            selectedKey: prov.selectedTeam,
            onSelected: (key) => prov.setSelectedTeam(key),
          ),
          if (canManageMembers)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  FilterChip(
                    label: const Text('Include Inactive', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600)),
                    selected: prov.includeInactive,
                    onSelected: (val) => prov.setIncludeInactive(val),
                    visualDensity: VisualDensity.compact,
                  ),
                  TextButton.icon(
                    onPressed: () => _showManageMemberSheet(context, prov, null),
                    icon: const Icon(Icons.add_rounded, size: 16),
                    label: const Text('Add Member', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 6),

          if (prov.isLoading && prov.members.isNotEmpty)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => prov.fetchMembers(),
              child: prov.isLoading && prov.members.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : prov.errorMessage != null && prov.members.isEmpty
                      ? EmptyStateWidget(
                          icon: Icons.cloud_off_rounded,
                          title: 'Could Not Load Team Roster',
                          description: prov.errorMessage!,
                          actionLabel: 'Retry',
                          actionIcon: Icons.refresh_rounded,
                          onAction: () => prov.fetchMembers(),
                        )
                      : filtered.isEmpty
                          ? const EmptyStateWidget(
                              icon: Icons.person_off_rounded,
                              title: 'No Specialists Found',
                              description: 'No team members found for this squad selection.',
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                              itemCount: filtered.length,
                              itemBuilder: (context, index) {
                                final member = filtered[index];
                                return _buildMemberCard(context, member, isDark, canManageMembers, prov);
                              },
                            ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMemberCard(BuildContext context, Specialist member, bool isDark, bool canManageMembers, TeamMembersProvider prov) {
    final bool isCallSignRedundant =
        member.callSign.trim().toLowerCase() == member.name.trim().toLowerCase() ||
        member.callSign.trim().isEmpty;
    final String roleSubtitle = isCallSignRedundant
        ? (member.professionalTitle.isNotEmpty ? member.professionalTitle : member.roleLabel)
        : 'Call Sign: ${member.callSign}';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
          width: 1,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => MemberProfileScreen(specialist: member),
              ),
            );
          },
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 20,
                          backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
                          backgroundImage: member.avatar.isNotEmpty ? NetworkImage(member.avatar) : null,
                          child: member.avatar.isEmpty
                              ? Text(
                                  member.name.isNotEmpty ? member.name[0].toUpperCase() : 'S',
                                  style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight),
                                )
                              : null,
                        ),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(
                                  member.name,
                                  style: TextStyle(
                                    color: isDark ? AppColors.textLight : AppColors.textDark,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(width: 6),
                                Text(
                                  member.customId,
                                  style: TextStyle(
                                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              roleSubtitle,
                              style: TextStyle(
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (member.status == 'inactive') ...[
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: AppColors.danger.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Text(
                              'INACTIVE',
                              style: TextStyle(color: AppColors.danger, fontSize: 9.5, fontWeight: FontWeight.w800),
                            ),
                          ),
                          const SizedBox(width: 6),
                        ],
                        if (member.team.isNotEmpty) TeamBadge(team: member.team),
                        if (canManageMembers) ...[
                          const SizedBox(width: 4),
                          IconButton(
                            icon: const Icon(Icons.edit_outlined, size: 18, color: AppColors.primaryBlueLight),
                            tooltip: 'Edit Member / Role / Quota',
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                            onPressed: () => _showManageMemberSheet(context, prov, member),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
                if (canManageMembers) ...[
                  const SizedBox(height: 6),
                  Text(
                    'Day Off: ${member.weeklyDayOff} • Annual Quota: ${member.annualQuota % 1 == 0 ? member.annualQuota.toInt() : member.annualQuota}d'
                    '${member.leaveAdjustment != 0 ? ' (Adj ${member.leaveAdjustment > 0 ? '+' : ''}${member.leaveAdjustment % 1 == 0 ? member.leaveAdjustment.toInt() : member.leaveAdjustment}d)' : ''}',
                    style: TextStyle(fontSize: 11, color: isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                  ),
                ],
                const SizedBox(height: 14),

                // 3-Column Metrics Row per GEMINI.md Defect 1.3
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'DELIVERIES',
                              style: TextStyle(
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                fontSize: 9,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 0.5,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '${member.deliveredProjects}',
                              style: const TextStyle(
                                color: AppColors.success,
                                fontSize: 14,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            Text(
                              'ACTIVE WIP',
                              style: TextStyle(
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                fontSize: 9,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 0.5,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '${member.activeProjects}',
                              style: const TextStyle(
                                color: AppColors.activeWip,
                                fontSize: 14,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              'TIPS EARNED',
                              style: TextStyle(
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                fontSize: 9,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 0.5,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '\$${member.totalTips.toStringAsFixed(0)}',
                              style: const TextStyle(
                                color: AppColors.warning,
                                fontSize: 14,
                                fontWeight: FontWeight.w800,
                              ),
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
        ),
      ),
    );
  }

  void _showManageMemberSheet(BuildContext context, TeamMembersProvider prov, Specialist? member) {
    final bool isEdit = member != null;
    final nameCtrl = TextEditingController(text: member?.name ?? '');
    final emailCtrl = TextEditingController(text: member?.email ?? '');
    final titleCtrl = TextEditingController(text: member?.professionalTitle ?? '');
    final customIdCtrl = TextEditingController(text: member?.customId ?? '');
    final quotaCtrl = TextEditingController(text: (member?.annualQuota ?? 28).toString());
    final adjCtrl = TextEditingController(text: (member?.leaveAdjustment ?? 0).toString());
    final passwordCtrl = TextEditingController();

    const squads = ['WpTitans', 'WpNexus', 'WpCraft', 'WpHorizon', 'Management'];
    String selectedTeam = squads.firstWhere(
      (s) => s.toLowerCase() == (member?.team ?? '').trim().toLowerCase(),
      orElse: () => 'WpTitans',
    );
    const roles = ['member', 'deputy_leader', 'leader', 'executive', 'management', 'manager'];
    String selectedRole = roles.contains(member?.teamRole) ? member!.teamRole : 'member';
    const days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    String selectedDayOff = days.contains(member?.weeklyDayOff) ? member!.weeklyDayOff : 'Sunday';
    String selectedStatus = (member?.status == 'inactive') ? 'inactive' : 'active';
    bool isSaving = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) {
          return Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom,
              left: 20,
              right: 20,
              top: 20,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        isEdit ? 'Edit Member: ${member.name}' : 'Add Agency Member',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                      ),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Text('Full Name *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: nameCtrl,
                    decoration: const InputDecoration(hintText: 'e.g. MD. Shakil Ahmed', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  const Text('Email Address *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: emailCtrl,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(hintText: 'member@fbinternationalbd.com', border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Squad', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<String>(
                              value: selectedTeam,
                              items: squads.map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
                              onChanged: (v) {
                                if (v != null) setSheetState(() => selectedTeam = v);
                              },
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Role', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<String>(
                              value: selectedRole,
                              items: const [
                                DropdownMenuItem(value: 'member', child: Text('Specialist')),
                                DropdownMenuItem(value: 'deputy_leader', child: Text('Deputy Leader')),
                                DropdownMenuItem(value: 'leader', child: Text('Team Leader')),
                                DropdownMenuItem(value: 'executive', child: Text('Executive (View)')),
                                DropdownMenuItem(value: 'management', child: Text('Management (View)')),
                                DropdownMenuItem(value: 'manager', child: Text('Super Admin')),
                              ],
                              onChanged: (v) {
                                if (v != null) setSheetState(() => selectedRole = v);
                              },
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Job Title', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: titleCtrl,
                              decoration: const InputDecoration(hintText: 'WordPress Developer', border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      SizedBox(
                        width: 115,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Member ID', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: customIdCtrl,
                              decoration: const InputDecoration(hintText: 'EMP-01', border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Weekly Day Off', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<String>(
                              value: selectedDayOff,
                              items: days.map((d) => DropdownMenuItem(value: d, child: Text(d))).toList(),
                              onChanged: (v) {
                                if (v != null) setSheetState(() => selectedDayOff = v);
                              },
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Account Status', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<String>(
                              value: selectedStatus,
                              items: const [
                                DropdownMenuItem(value: 'active', child: Text('Active')),
                                DropdownMenuItem(value: 'inactive', child: Text('Inactive')),
                              ],
                              onChanged: (v) {
                                if (v != null) setSheetState(() => selectedStatus = v);
                              },
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Annual Leave Quota', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: quotaCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Balance Adj (+/-)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: adjCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true),
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(isEdit ? 'Reset Password (Optional)' : 'Initial Password (Optional)', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: passwordCtrl,
                    obscureText: true,
                    decoration: InputDecoration(
                      hintText: isEdit ? 'Leave blank to keep current password' : 'Leave blank to auto-generate',
                      border: const OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    height: 46,
                    child: ElevatedButton(
                      onPressed: isSaving
                          ? null
                          : () async {
                              final name = nameCtrl.text.trim();
                              final email = emailCtrl.text.trim();
                              if (name.isEmpty || email.isEmpty) {
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  const SnackBar(content: Text('Full Name and Email are required.')),
                                );
                                return;
                              }
                              setSheetState(() => isSaving = true);
                              final payload = <String, dynamic>{
                                'display_name': name,
                                'email': email,
                                'team': selectedTeam,
                                'team_role': selectedRole,
                                'professional_title': titleCtrl.text.trim(),
                                'custom_id': customIdCtrl.text.trim(),
                                'weekly_day_off': selectedDayOff,
                                'annual_quota': double.tryParse(quotaCtrl.text.trim()) ?? 28.0,
                                'leave_adjustment': double.tryParse(adjCtrl.text.trim()) ?? 0.0,
                                'status': selectedStatus,
                              };
                              if (passwordCtrl.text.trim().isNotEmpty) {
                                payload['password'] = passwordCtrl.text.trim();
                              }

                              final String? error = isEdit
                                  ? await prov.updateMember(member.id, payload)
                                  : await prov.createMember(payload);
                              if (!ctx.mounted) return;
                              setSheetState(() => isSaving = false);
                              if (error == null) {
                                Navigator.pop(ctx);
                                if (!context.mounted) return;
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(isEdit ? 'Member updated successfully!' : 'Member created successfully!'),
                                    backgroundColor: AppColors.success,
                                  ),
                                );
                              } else {
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  SnackBar(content: Text(error), backgroundColor: AppColors.danger),
                                );
                              }
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryBlue,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      child: isSaving
                          ? const CircularProgressIndicator(color: Colors.white)
                          : Text(isEdit ? 'Save Member Changes' : 'Create Member', style: const TextStyle(fontWeight: FontWeight.w800)),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
