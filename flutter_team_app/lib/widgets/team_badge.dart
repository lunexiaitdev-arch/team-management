import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

class TeamBadge extends StatelessWidget {
  final String team;
  final bool isSmall;

  const TeamBadge({
    super.key,
    required this.team,
    this.isSmall = false,
  });

  @override
  Widget build(BuildContext context) {
    if (team.isEmpty) return const SizedBox.shrink();

    final color = AppColors.getTeamColor(team);

    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: isSmall ? 8 : 10,
        vertical: isSmall ? 2 : 4,
      ),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.25), width: 1),
      ),
      child: Text(
        team,
        style: TextStyle(
          color: color,
          fontSize: isSmall ? 10 : 11,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.3,
        ),
      ),
    );
  }
}
