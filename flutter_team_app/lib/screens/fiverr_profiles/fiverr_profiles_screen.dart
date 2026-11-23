import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/profiles_provider.dart';
import '../../models/fiverr_profile.dart';
import '../../theme/app_colors.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/drawer_opener.dart';

class FiverrProfilesScreen extends StatefulWidget {
  const FiverrProfilesScreen({super.key});

  @override
  State<FiverrProfilesScreen> createState() => _FiverrProfilesScreenState();
}

class _FiverrProfilesScreenState extends State<FiverrProfilesScreen> {
  final _nameController = TextEditingController();
  final _urlController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<ProfilesProvider>(context, listen: false).fetchProfiles();
    });
  }

  @override
  void dispose() {
    _nameController.dispose();
    _urlController.dispose();
    super.dispose();
  }

  void _showAddProfileDialog(BuildContext context, ProfilesProvider prov) {
    _nameController.clear();
    _urlController.clear();

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Add Fiverr Profile'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: _nameController,
              decoration: const InputDecoration(labelText: 'Profile Name', hintText: 'e.g. wp_expert_pro'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _urlController,
              decoration: const InputDecoration(labelText: 'Fiverr URL', hintText: 'https://www.fiverr.com/...'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              final name = _nameController.text.trim();
              if (name.isEmpty) return;
              Navigator.of(ctx).pop();
              final success = await prov.createProfile(name, _urlController.text.trim(), 'active');
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(success ? 'Profile added successfully!' : 'Failed to add profile'),
                    backgroundColor: success ? AppColors.success : AppColors.danger,
                  ),
                );
              }
            },
            child: const Text('Save Profile'),
          ),
        ],
      ),
    );
  }

  void _confirmDelete(BuildContext context, ProfilesProvider prov, FiverrProfile p) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Profile'),
        content: Text('Are you sure you want to remove ${p.name}?'),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(ctx).pop();
              final success = await prov.deleteProfile(p.id);
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(success ? 'Profile removed' : 'Failed to remove profile'),
                    backgroundColor: success ? AppColors.success : AppColors.danger,
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<ProfilesProvider>(context);
    final auth = Provider.of<AuthProvider>(context);

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
        title: const Text('Fiverr Agency Accounts'),
        actions: [
          if ((auth.isAdmin || auth.isLeader) && !auth.isViewOnly)
            IconButton(
              icon: const Icon(Icons.add_circle_outline_rounded),
              onPressed: () => _showAddProfileDialog(context, prov),
              tooltip: 'Add Profile',
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => prov.fetchProfiles(),
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: Column(
        children: [
          if (prov.isLoading && prov.profiles.isNotEmpty)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => prov.fetchProfiles(),
              child: prov.isLoading && prov.profiles.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : prov.errorMessage != null && prov.profiles.isEmpty
                      ? EmptyStateWidget(
                          icon: Icons.cloud_off_rounded,
                          title: 'Could Not Load Profiles',
                          description: prov.errorMessage!,
                          actionLabel: 'Retry',
                          actionIcon: Icons.refresh_rounded,
                          onAction: () => prov.fetchProfiles(),
                        )
                      : prov.profiles.isEmpty
                          ? EmptyStateWidget(
                              icon: Icons.store_rounded,
                              title: 'No Fiverr Profiles',
                              description: 'No managed agency Fiverr accounts added yet.',
                              actionLabel: ((auth.isAdmin || auth.isLeader) && !auth.isViewOnly) ? 'Add Fiverr Account' : null,
                              onAction: ((auth.isAdmin || auth.isLeader) && !auth.isViewOnly) ? () => _showAddProfileDialog(context, prov) : null,
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: prov.profiles.length,
                              itemBuilder: (context, index) {
                                final p = prov.profiles[index];
                                return _buildProfileCard(context, p, prov, auth, isDark);
                              },
                            ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileCard(
    BuildContext context,
    FiverrProfile p,
    ProfilesProvider prov,
    AuthProvider auth,
    bool isDark,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
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
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: AppColors.success.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.storefront_rounded, size: 20, color: AppColors.success),
                  ),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        p.name,
                        style: TextStyle(
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      if (p.url.isNotEmpty)
                        Text(
                          p.url,
                          style: TextStyle(
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            fontSize: 11,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                    ],
                  ),
                ],
              ),
              if (auth.isOperationsManager && !auth.isViewOnly)
                IconButton(
                  icon: const Icon(Icons.delete_outline_rounded, color: AppColors.danger, size: 20),
                  onPressed: () => _confirmDelete(context, prov, p),
                ),
            ],
          ),
          const SizedBox(height: 14),

          // Stats Mini Grid
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                Column(
                  children: [
                    Text(
                      'TOTAL ORDERS',
                      style: TextStyle(color: isDark ? AppColors.textDim : AppColors.textDarkMuted, fontSize: 10, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${p.totalProjects}',
                      style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 14, fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
                Container(height: 28, width: 1, color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                Column(
                  children: [
                    const Text(
                      'ACTIVE WIP',
                      style: TextStyle(color: AppColors.activeWip, fontSize: 10, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${p.activeProjects}',
                      style: const TextStyle(color: AppColors.activeWip, fontSize: 14, fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
                Container(height: 28, width: 1, color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                Column(
                  children: [
                    const Text(
                      'COMPLETED',
                      style: TextStyle(color: AppColors.success, fontSize: 10, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${p.completedProjects}',
                      style: const TextStyle(color: AppColors.success, fontSize: 14, fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
