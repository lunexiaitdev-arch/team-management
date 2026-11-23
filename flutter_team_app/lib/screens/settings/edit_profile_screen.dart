import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/team_members_provider.dart';
import '../../theme/app_colors.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _nameController = TextEditingController();
  final _callSignController = TextEditingController();
  final _titleController = TextEditingController();
  final _bioController = TextEditingController();
  final _skillsController = TextEditingController();

  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    final user = Provider.of<AuthProvider>(context, listen: false).currentUser;
    if (user != null) {
      _nameController.text = user.name;
      _callSignController.text = user.callSign;
      _titleController.text = user.professionalTitle;
      WidgetsBinding.instance.addPostFrameCallback((_) async {
        final membersProv = Provider.of<TeamMembersProvider>(context, listen: false);
        final details = await membersProv.getMemberProfile(user.id);
        if (!mounted || details == null) return;
        final profile = details['profile'];
        if (profile is Map) {
          final bio = profile['bio']?.toString() ?? '';
          final rawSkills = profile['skills'];
          final skillsText = rawSkills is List
              ? rawSkills.join(', ')
              : (rawSkills?.toString() ?? '');
          if (bio.isNotEmpty && _bioController.text.isEmpty) {
            _bioController.text = bio;
          }
          if (skillsText.isNotEmpty && _skillsController.text.isEmpty) {
            _skillsController.text = skillsText;
          }
        }
      });
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _callSignController.dispose();
    _titleController.dispose();
    _bioController.dispose();
    _skillsController.dispose();
    super.dispose();
  }

  Future<void> _handleSave() async {
    final name = _nameController.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Display name cannot be empty.')),
      );
      return;
    }

    setState(() => _isSaving = true);
    final auth = Provider.of<AuthProvider>(context, listen: false);

    final payload = {
      'display_name': name,
      'call_sign': _callSignController.text.trim(),
      'professional_title': _titleController.text.trim(),
      'bio': _bioController.text.trim(),
      'skills': _skillsController.text.trim(),
    };

    final success = await auth.updateUserProfile(payload);
    setState(() => _isSaving = false);

    if (mounted) {
      if (success) {
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Profile updated successfully!'), backgroundColor: AppColors.success),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(auth.errorMessage ?? 'Failed to save changes'), backgroundColor: AppColors.danger),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Edit Professional Profile'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildLabel('DISPLAY NAME *', isDark),
            const SizedBox(height: 6),
            TextField(
              controller: _nameController,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'Full display name'),
            ),
            const SizedBox(height: 16),

            _buildLabel('AGENCY CALL SIGN', isDark),
            const SizedBox(height: 6),
            TextField(
              controller: _callSignController,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'e.g. Lead Dev / Senior Specialist'),
            ),
            const SizedBox(height: 16),

            _buildLabel('PROFESSIONAL TITLE', isDark),
            const SizedBox(height: 6),
            TextField(
              controller: _titleController,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'e.g. WordPress Specialist & UI Engineer'),
            ),
            const SizedBox(height: 16),

            _buildLabel('VERIFIED SKILLS (COMMA SEPARATED)', isDark),
            const SizedBox(height: 6),
            TextField(
              controller: _skillsController,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'e.g. Elementor Pro, WooCommerce, CSS3, Speed Optimization'),
            ),
            const SizedBox(height: 16),

            _buildLabel('BIO / SPECIALTY STATEMENT', isDark),
            const SizedBox(height: 6),
            TextField(
              controller: _bioController,
              maxLines: 4,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'Brief summary of your expertise, deliverable focus, and tools...'),
            ),
            const SizedBox(height: 28),

            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _isSaving ? null : _handleSave,
                child: _isSaving
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('Save Profile Changes', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLabel(String label, bool isDark) {
    return Text(
      label,
      style: TextStyle(
        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
        fontSize: 11,
        fontWeight: FontWeight.w800,
        letterSpacing: 0.5,
      ),
    );
  }
}
