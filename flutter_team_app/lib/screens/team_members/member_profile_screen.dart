import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/user.dart';
import '../../providers/auth_provider.dart';
import '../../providers/team_members_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/team_badge.dart';
import 'add_milestone_sheet.dart';

class MemberProfileScreen extends StatefulWidget {
  final Specialist specialist;

  const MemberProfileScreen({super.key, required this.specialist});

  @override
  State<MemberProfileScreen> createState() => _MemberProfileScreenState();
}

class _MemberProfileScreenState extends State<MemberProfileScreen> {
  Map<String, dynamic>? _profileDetails;
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchDetails();
  }

  Future<void> _fetchDetails() async {
    if (mounted) {
      setState(() {
        _isLoading = _profileDetails == null;
        _errorMessage = null;
      });
    }
    try {
      final prov = Provider.of<TeamMembersProvider>(context, listen: false);
      final details = await prov.getMemberProfile(widget.specialist.id);
      if (mounted) {
        setState(() {
          _profileDetails = details;
          if (details == null && prov.errorMessage != null) {
            _errorMessage = prov.errorMessage;
          }
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _errorMessage = e.toString());
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context);
    final s = widget.specialist;

    final profile = _profileDetails?['profile'] as Map<String, dynamic>? ?? {};
    final milestonesRaw = _profileDetails?['milestones'] as List<dynamic>? ?? [];
    final milestones = milestonesRaw.map((m) => MemberMilestone.fromJson(Map<String, dynamic>.from(m))).toList();

    final bio = profile['bio'] ?? '';
    final skillsStr = profile['skills'] ?? 'WordPress, Elementor Pro, WooCommerce, CSS3, Speed Optimization';
    final skills = skillsStr.toString().split(',').map((s) => s.trim()).where((s) => s.isNotEmpty).toList();
    final joiningDate = profile['joining_date'] ?? 'Jan 2025';

    return Scaffold(
      appBar: AppBar(
        title: Text('${s.name} Profile'),
        actions: [
          if ((auth.isAdmin || auth.isLeader) && !auth.isViewOnly)
            IconButton(
              icon: const Icon(Icons.add_task_rounded),
              onPressed: () {
                showModalBottomSheet(
                  context: context,
                  isScrollControlled: true,
                  builder: (_) => AddMilestoneSheet(userId: s.id, onAdded: _fetchDetails),
                );
              },
              tooltip: 'Add Milestone',
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _fetchDetails,
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchDetails,
        child: _isLoading && _profileDetails == null
            ? const Center(child: CircularProgressIndicator())
            : _profileDetails == null && _errorMessage != null
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(24),
                    children: [
                      const SizedBox(height: 60),
                      const Icon(Icons.cloud_off_rounded, size: 48, color: AppColors.textDim),
                      const SizedBox(height: 12),
                      Text(
                        'Failed to Load Profile',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        _errorMessage!,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          fontSize: 13,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Center(
                        child: ElevatedButton.icon(
                          onPressed: _fetchDetails,
                          icon: const Icon(Icons.refresh_rounded, size: 18),
                          label: const Text('Retry'),
                        ),
                      ),
                    ],
                  )
                : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                // Profile Header Card
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkCard : AppColors.lightCard,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                  ),
                  child: Column(
                    children: [
                      CircleAvatar(
                        radius: 40,
                        backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
                        backgroundImage: s.avatar.isNotEmpty ? NetworkImage(s.avatar) : null,
                        child: s.avatar.isEmpty
                            ? Text(
                                s.name.isNotEmpty ? s.name[0].toUpperCase() : 'S',
                                style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: AppColors.primaryBlueLight),
                              )
                            : null,
                      ),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            s.name,
                            style: TextStyle(
                              color: isDark ? AppColors.textLight : AppColors.textDark,
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            s.customId,
                            style: const TextStyle(
                              color: AppColors.primaryBlueLight,
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        s.professionalTitle.isNotEmpty ? s.professionalTitle : s.roleLabel,
                        style: TextStyle(
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 10),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          if (s.team.isNotEmpty) TeamBadge(team: s.team),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                            ),
                            child: Text(
                              'Joined: $joiningDate',
                              style: TextStyle(
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                fontSize: 11,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // Deliveries & Stats Mini-Grid
                Row(
                  children: [
                    Expanded(
                      child: _buildStatBox('TOTAL ORDERS', '${s.totalProjects}', isDark),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _buildStatBox('ACCEPTED', '${s.deliveredProjects}', isDark, AppColors.success),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _buildStatBox('ACTIVE WIP', '${s.activeProjects}', isDark, AppColors.activeWip),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // Bio Summary
                if (bio.isNotEmpty) ...[
                  _buildSectionTitle('SPECIALIST BIO', isDark),
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: isDark ? AppColors.darkCard : AppColors.lightCard,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                    ),
                    child: Text(
                      bio,
                      style: TextStyle(
                        color: isDark ? AppColors.textLight : AppColors.textDark,
                        fontSize: 13,
                        height: 1.4,
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],

                // Skills Tags
                _buildSectionTitle('VERIFIED SKILLS & SPECIALTIES', isDark),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: skills.map((skill) {
                    return Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: AppColors.primaryBlue.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.primaryBlue.withOpacity(0.25)),
                      ),
                      child: Text(
                        skill,
                        style: const TextStyle(
                          color: AppColors.primaryBlueLight,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 24),

                // Career Milestones Timeline
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildSectionTitle('CAREER MILESTONES & RECOGNITION', isDark),
                    if ((auth.isAdmin || auth.isLeader) && !auth.isViewOnly)
                      TextButton.icon(
                        onPressed: () {
                          showModalBottomSheet(
                            context: context,
                            isScrollControlled: true,
                            builder: (_) => AddMilestoneSheet(userId: s.id, onAdded: _fetchDetails),
                          );
                        },
                        icon: const Icon(Icons.add_rounded, size: 14),
                        label: const Text('Add Milestone', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                      ),
                  ],
                ),
                const SizedBox(height: 8),

                if (milestones.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isDark ? AppColors.darkCard : AppColors.lightCard,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                    ),
                    child: const Center(
                      child: Text('No career milestones logged yet.', style: TextStyle(color: AppColors.textDim, fontSize: 13)),
                    ),
                  )
                else
                  ...milestones.map((milestone) {
                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: isDark ? AppColors.darkCard : AppColors.lightCard,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: AppColors.cyanAccent.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.workspace_premium_rounded, size: 18, color: AppColors.cyanAccent),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      milestone.title,
                                      style: TextStyle(
                                        color: isDark ? AppColors.textLight : AppColors.textDark,
                                        fontSize: 13,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    Text(
                                      milestone.milestoneDate,
                                      style: const TextStyle(color: AppColors.textDim, fontSize: 11),
                                    ),
                                  ],
                                ),
                                if (milestone.description.isNotEmpty) ...[
                                  const SizedBox(height: 4),
                                  Text(
                                    milestone.description,
                                    style: TextStyle(
                                      color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                                      fontSize: 12,
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
              ],
            ),
      ),
    );
  }

  Widget _buildStatBox(String title, String value, bool isDark, [Color? valueColor]) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
      ),
      child: Column(
        children: [
          Text(
            title,
            style: TextStyle(
              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              fontSize: 10,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              color: valueColor ?? (isDark ? AppColors.textLight : AppColors.textDark),
              fontSize: 18,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title, bool isDark) {
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
}
