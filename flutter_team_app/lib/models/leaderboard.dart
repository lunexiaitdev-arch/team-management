class LeaderboardItem {
  final int rank;
  final String rankLabel;
  final int userId;
  final String name;
  final String avatar;
  final String callSign;
  final String team;
  final String role;
  final String customId;
  final int completedOrders;
  final double netRevenue;
  final double grossRevenue;
  final double activeWip;
  final double tips;
  final double onTimeRate;
  final int onTimeCount;
  final double clientRating;
  final int reviewCount;
  final double cancellationRate;
  final double score;
  final bool isTopPerformer;
  final String badge;

  LeaderboardItem({
    required this.rank,
    required this.rankLabel,
    required this.userId,
    required this.name,
    required this.avatar,
    required this.callSign,
    required this.team,
    this.role = 'Specialist',
    required this.customId,
    required this.completedOrders,
    required this.netRevenue,
    this.grossRevenue = 0.0,
    this.activeWip = 0.0,
    required this.tips,
    this.onTimeRate = 0.0,
    this.onTimeCount = 0,
    this.clientRating = 0.0,
    this.reviewCount = 0,
    this.cancellationRate = 0.0,
    required this.score,
    this.isTopPerformer = false,
    this.badge = '',
  });

  factory LeaderboardItem.fromJson(Map<String, dynamic> json, [int defaultRank = 0]) {
    final double net = double.tryParse(
      json['net_delivered_revenue']?.toString() ??
      json['total_delivery']?.toString() ??
      json['net_revenue']?.toString() ??
      json['net']?.toString() ??
      '0',
    ) ?? 0.0;

    final double gross = double.tryParse(
      json['gross_revenue']?.toString() ??
      json['total_delivery_gross']?.toString() ??
      '0',
    ) ?? (net > 0 ? (net / 0.8) : 0.0);

    final String rawRank = json['rank']?.toString() ?? defaultRank.toString();
    final int parsedRank = int.tryParse(rawRank) ?? defaultRank;

    final String memberName = (json['member_name'] ?? json['name'] ?? json['display_name'] ?? 'Specialist').toString();
    final String customBadge = (json['member_custom_id'] ?? json['custom_id'] ?? json['badge_id'] ?? '#${json['user_id'] ?? json['id'] ?? 0}').toString();

    final String avatarUrl = (json['avatar_url'] ?? json['avatar'] ?? '').toString();

    return LeaderboardItem(
      rank: parsedRank,
      rankLabel: rawRank == '-' ? '-' : '#$parsedRank',
      userId: int.tryParse(json['user_id']?.toString() ?? json['id']?.toString() ?? '0') ?? 0,
      name: memberName,
      avatar: avatarUrl.isNotEmpty ? avatarUrl : 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(memberName)}&background=2563eb&color=fff&size=128&bold=true',
      callSign: (json['call_sign'] ?? memberName).toString(),
      team: (json['team_name'] ?? json['team'] ?? '').toString(),
      role: (json['role_label'] ?? json['role_slug'] ?? json['role'] ?? 'Specialist').toString(),
      customId: customBadge.startsWith('#') ? customBadge : '#$customBadge',
      completedOrders: int.tryParse(
        json['completed_orders']?.toString() ??
        json['total_completed']?.toString() ??
        json['deliveries']?.toString() ??
        '0',
      ) ?? 0,
      netRevenue: net,
      grossRevenue: gross,
      activeWip: double.tryParse(json['active_wip']?.toString() ?? json['total_wip']?.toString() ?? '0') ?? 0.0,
      tips: double.tryParse(json['tips']?.toString() ?? json['total_tips']?.toString() ?? '0') ?? 0.0,
      onTimeRate: double.tryParse(json['on_time_rate']?.toString() ?? '0') ?? 0.0,
      onTimeCount: int.tryParse(json['on_time_count']?.toString() ?? json['delivered_without_extension']?.toString() ?? '0') ?? 0,
      clientRating: double.tryParse(json['client_rating']?.toString() ?? json['rating']?.toString() ?? '0') ?? 0.0,
      reviewCount: int.tryParse(json['review_count']?.toString() ?? '0') ?? 0,
      cancellationRate: double.tryParse(json['cancellation_rate']?.toString() ?? '0') ?? 0.0,
      score: double.tryParse(json['performance_score']?.toString() ?? json['score']?.toString() ?? json['points']?.toString() ?? '0') ?? 0.0,
      isTopPerformer: json['is_top_performer'] == true || parsedRank == 1,
      badge: json['badge']?.toString() ?? '',
    );
  }
}

class LeaderboardData {
  final String period;
  final String periodLabel;
  final String selectedTeam;
  final String sortBy;
  final List<LeaderboardItem> podium;
  final List<LeaderboardItem> rankings;
  final String methodology;

  LeaderboardData({
    required this.period,
    required this.periodLabel,
    this.selectedTeam = 'all',
    this.sortBy = 'revenue',
    required this.podium,
    required this.rankings,
    this.methodology = '',
  });

  factory LeaderboardData.fromJson(Map<String, dynamic> json) {
    List<LeaderboardItem> parsedPodium = [];
    if (json['podium'] is List) {
      int idx = 1;
      parsedPodium = (json['podium'] as List)
          .map((item) => LeaderboardItem.fromJson(Map<String, dynamic>.from(item), idx++))
          .toList();
    }

    List<LeaderboardItem> parsedRankings = [];
    if (json['rankings'] is List) {
      int rIdx = 1;
      parsedRankings = (json['rankings'] as List)
          .map((item) => LeaderboardItem.fromJson(Map<String, dynamic>.from(item), rIdx++))
          .toList();
    }

    String parsedMethodology = '';
    if (json['methodology'] is Map) {
      final meth = json['methodology'] as Map;
      final scale = meth['scale'] ?? '0 to 100 points';
      final pillars = meth['pillars'] is List ? (meth['pillars'] as List) : [];
      final lines = <String>['Scoring Scale: $scale'];
      for (final p in pillars) {
        if (p is Map) {
          lines.add('• ${p['name']} (${p['weight']}): ${p['formula'] ?? ''}');
        }
      }
      if (meth['neutral_rating_policy'] != null) {
        lines.add('\n${meth['neutral_rating_policy']}');
      }
      if (meth['minimum_orders_rule'] != null) {
        lines.add('\n${meth['minimum_orders_rule']}');
      }
      parsedMethodology = lines.join('\n');
    } else if (json['methodology'] is String) {
      parsedMethodology = json['methodology'];
    }

    return LeaderboardData(
      period: (json['period'] ?? json['period_key'] ?? '').toString(),
      periodLabel: (json['period_label'] ?? 'Performance Leaderboard').toString(),
      selectedTeam: (json['selected_team'] ?? 'all').toString(),
      sortBy: (json['sort_by'] ?? 'revenue').toString(),
      podium: parsedPodium,
      rankings: parsedRankings,
      methodology: parsedMethodology,
    );
  }
}
