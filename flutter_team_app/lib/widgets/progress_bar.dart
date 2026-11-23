import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

class CustomProgressBar extends StatelessWidget {
  final int progressPercent;
  final double height;
  final bool showLabel;

  const CustomProgressBar({
    super.key,
    required this.progressPercent,
    this.height = 6,
    this.showLabel = true,
  });

  @override
  Widget build(BuildContext context) {
    final clamped = progressPercent.clamp(0, 100);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    Color progressColor;
    if (clamped >= 100) {
      progressColor = AppColors.success;
    } else if (clamped >= 50) {
      progressColor = AppColors.primaryBlueLight;
    } else {
      progressColor = AppColors.cyanAccent;
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (showLabel) ...[
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Progress',
                style: TextStyle(
                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                ),
              ),
              Text(
                '$clamped%',
                style: TextStyle(
                  color: progressColor,
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
        ],
        Container(
          height: height,
          width: double.infinity,
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
            borderRadius: BorderRadius.circular(height / 2),
          ),
          child: FractionallySizedBox(
            alignment: Alignment.centerLeft,
            widthFactor: clamped / 100.0,
            child: Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    progressColor.withOpacity(0.8),
                    progressColor,
                  ],
                ),
                borderRadius: BorderRadius.circular(height / 2),
                boxShadow: [
                  BoxShadow(
                    color: progressColor.withOpacity(0.4),
                    blurRadius: 4,
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}
