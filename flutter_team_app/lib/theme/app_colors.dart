import 'package:flutter/material.dart';

class AppColors {
  // Brand Accents (Exact FBIBD Team Brand Blue from Icon: #0689E3)
  static const Color primaryBlue = Color(0xFF0689E3);
  static const Color primaryBlueLight = Color(0xFF29A3FF);
  static const Color primaryBlueDark = Color(0xFF006EC0);
  static const Color cyanAccent = Color(0xFF06B6D4);
  static const Color cyanAccentLight = Color(0xFF22D3EE);
  static const Color purpleAccent = Color(0xFF8B5CF6);
  static const Color purpleAccentDark = Color(0xFF7C3AED);

  // Backgrounds & Surfaces (Dark Theme)
  static const Color darkBackground = Color(0xFF0B0F19);
  static const Color darkSurface = Color(0xFF161F30);
  static const Color darkCard = Color(0xFF1E293B);
  static const Color darkBorder = Color(0xFF334155);
  static const Color darkDivider = Color(0xFF1E293B);

  // Backgrounds & Surfaces (Light Theme)
  static const Color lightBackground = Color(0xFFF8FAFC);
  static const Color lightSurface = Color(0xFFFFFFFF);
  static const Color lightCard = Color(0xFFFFFFFF);
  static const Color lightBorder = Color(0xFFE2E8F0);
  static const Color lightDivider = Color(0xFFF1F5F9);

  // Functional Status Colors
  static const Color success = Color(0xFF10B981); // Completed / Accepted
  static const Color successBg = Color(0xFF064E3B);
  static const Color warning = Color(0xFFF59E0B); // Revision / Pending
  static const Color warningBg = Color(0xFF78350F);
  static const Color danger = Color(0xFFEF4444);  // Cancelled / Overdue
  static const Color dangerBg = Color(0xFF7F1D1D);
  static const Color info = Color(0xFF0EA5E9);    // NRA / In Review
  static const Color infoBg = Color(0xFF0C4A6E);
  static const Color activeWip = Color(0xFF3B82F6);

  // Text Colors
  static const Color textLight = Color(0xFFF8FAFC);
  static const Color textMuted = Color(0xFF94A3B8);
  static const Color textDim = Color(0xFF64748B);
  static const Color textDark = Color(0xFF0F172A);
  static const Color textDarkMuted = Color(0xFF475569);

  // Team Colors
  static const Color teamTitans = Color(0xFF3B82F6);
  static const Color teamNexus = Color(0xFF8B5CF6);
  static const Color teamCraft = Color(0xFF10B981);
  static const Color teamHorizon = Color(0xFFF59E0B);
  static const Color teamManagement = Color(0xFFEC4899);

  // Linear Gradients
  static const LinearGradient brandGradient = LinearGradient(
    colors: [primaryBlue, cyanAccent],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient purpleGradient = LinearGradient(
    colors: [purpleAccentDark, primaryBlue],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient cardGlowGradient = LinearGradient(
    colors: [Color(0x1A2563EB), Color(0x0506B6D4)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient goldGradient = LinearGradient(
    colors: [Color(0xFFFFD700), Color(0xFFFFA500)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient silverGradient = LinearGradient(
    colors: [Color(0xFFE2E8F0), Color(0xFF94A3B8)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient bronzeGradient = LinearGradient(
    colors: [Color(0xFFCD7F32), Color(0xFFA0522D)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static Color getTeamColor(String team) {
    switch (team.toLowerCase().replaceAll(' ', '')) {
      case 'wptitans':
        return teamTitans;
      case 'wpnexus':
        return teamNexus;
      case 'wpcraft':
        return teamCraft;
      case 'wphorizon':
        return teamHorizon;
      case 'management':
        return teamManagement;
      default:
        return primaryBlue;
    }
  }

  static Color getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'completed':
      case 'delivery_accepted':
        return success;
      case 'revision':
      case 'delivery_submitted':
        return warning;
      case 'cancelled':
      case 'overdue':
        return danger;
      case 'wip':
        return activeWip;
      case 'nra':
      default:
        return info;
    }
  }
}
