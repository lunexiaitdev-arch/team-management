class ProjectAssignee {
  final int userId;
  final String name;
  final String avatar;
  final String callSign;
  final String team;
  final String roleLabel;
  final double splitPercent;

  ProjectAssignee({
    required this.userId,
    required this.name,
    required this.avatar,
    required this.callSign,
    required this.team,
    this.roleLabel = 'Specialist',
    this.splitPercent = 100.0,
  });

  factory ProjectAssignee.fromJson(Map<String, dynamic> json) {
    final memberName = (json['name'] ?? json['display_name'] ?? '').toString();
    final customAvatar = (json['avatar'] ?? json['avatar_url'] ?? '').toString();

    return ProjectAssignee(
      userId: int.tryParse(json['user_id']?.toString() ?? json['id']?.toString() ?? '0') ?? 0,
      name: memberName,
      avatar: customAvatar.isNotEmpty
          ? customAvatar
          : 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(memberName.isNotEmpty ? memberName : "Specialist")}&background=2563eb&color=fff&size=128&bold=true',
      callSign: (json['call_sign'] ?? json['name'] ?? memberName).toString(),
      team: (json['team'] ?? json['team_name'] ?? '').toString(),
      roleLabel: (json['role_label'] ?? json['role'] ?? 'Specialist').toString(),
      splitPercent: double.tryParse(json['split_percentage']?.toString() ?? json['split_percent']?.toString() ?? '100') ?? 100.0,
    );
  }
}

class ProjectNote {
  final int id;
  final int userId;
  final String authorName;
  final String note;
  final String createdAt;

  ProjectNote({
    required this.id,
    required this.userId,
    required this.authorName,
    required this.note,
    required this.createdAt,
  });

  factory ProjectNote.fromJson(Map<String, dynamic> json) {
    return ProjectNote(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      authorName: (json['author_name'] ?? json['user_name'] ?? 'Team Member').toString(),
      note: (json['note'] ?? json['content'] ?? '').toString(),
      createdAt: (json['created_at'] ?? '').toString(),
    );
  }
}

class ProjectSplit {
  final int id;
  final String partyLabel;
  final String partyKind; // 'team', 'member', 'external'
  final String skill;
  final double shareNet;
  final String note;

  ProjectSplit({
    required this.id,
    required this.partyLabel,
    this.partyKind = 'team',
    this.skill = '',
    required this.shareNet,
    this.note = '',
  });

  factory ProjectSplit.fromJson(Map<String, dynamic> json) {
    return ProjectSplit(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      partyLabel: (json['party_label'] ?? json['label'] ?? 'Partner').toString(),
      partyKind: (json['party_kind'] ?? 'team').toString(),
      skill: (json['skill'] ?? '').toString(),
      shareNet: double.tryParse(json['share_net']?.toString() ?? json['amount']?.toString() ?? '0') ?? 0.0,
      note: (json['note'] ?? '').toString(),
    );
  }
}

class CombinedSummary {
  final bool isCombined;
  final double orderTotalValue;
  final double myShare;
  final double mySharePercent;
  final double othersTotal;
  final double unallocated;
  final double overAllocated;
  final List<ProjectSplit> splits;

  CombinedSummary({
    this.isCombined = false,
    this.orderTotalValue = 0.0,
    this.myShare = 0.0,
    this.mySharePercent = 100.0,
    this.othersTotal = 0.0,
    this.unallocated = 0.0,
    this.overAllocated = 0.0,
    this.splits = const [],
  });

  factory CombinedSummary.fromJson(Map<String, dynamic> json) {
    List<ProjectSplit> parsedSplits = [];
    if (json['splits'] is List) {
      parsedSplits = (json['splits'] as List)
          .map((s) => ProjectSplit.fromJson(Map<String, dynamic>.from(s)))
          .toList();
    }
    return CombinedSummary(
      isCombined: json['is_combined'] == true,
      orderTotalValue: double.tryParse(json['order_total_value']?.toString() ?? '0') ?? 0.0,
      myShare: double.tryParse(json['my_share']?.toString() ?? '0') ?? 0.0,
      mySharePercent: double.tryParse(json['my_share_percent']?.toString() ?? '100') ?? 100.0,
      othersTotal: double.tryParse(json['others_total']?.toString() ?? '0') ?? 0.0,
      unallocated: double.tryParse(json['unallocated']?.toString() ?? '0') ?? 0.0,
      overAllocated: double.tryParse(json['over_allocated']?.toString() ?? '0') ?? 0.0,
      splits: parsedSplits,
    );
  }
}

class ProjectItem {
  final int id;
  final String orderId;
  final int? fiverrProfileId;
  final String profileName;
  final String buyerUsername;
  final String title;
  final double orderValue;
  final double netValue;
  final double orderTotalValue;
  final String orderType;
  final String subscriptionStatus;
  final String? subscriptionNextDate;
  final int subscriptionTermDays;
  final int subscriptionParentId;
  final bool isCombined;
  final double othersValue;
  final CombinedSummary? combined;
  final List<ProjectSplit> splits;
  final String deliveryDate;
  final String deliveredDate;
  final bool isDelivered;
  final String deliveryOutcomeStatus;
  final String deliveryOutcomeLabel;
  final int daysRemaining;
  final String daysRemainingBadge;
  final String daysRemainingUrgency;
  final String status;
  final int progressPercent;
  final String team;
  final String latestProgress;
  final double tips;
  final String createdAt;
  final List<ProjectAssignee> assignees;
  final List<ProjectNote> notes;
  final int extensionCount;
  final int extensionDays;
  final String extensionReason;
  final bool deliveredWithoutExtension;
  final bool isOntime;
  final String tipDate;
  final String clientReviewed;
  final int starRating;
  final String reviewFeedback;
  final String projectSheetLink;
  final String orderSource;
  final String orderLink;
  final bool isCarryForward;
  final int carryForwardDays;

  ProjectItem({
    required this.id,
    required this.orderId,
    this.fiverrProfileId,
    required this.profileName,
    required this.buyerUsername,
    required this.title,
    required this.orderValue,
    required this.netValue,
    this.orderTotalValue = 0.0,
    this.orderType = 'standard',
    this.subscriptionStatus = '',
    this.subscriptionNextDate,
    this.subscriptionTermDays = 7,
    this.subscriptionParentId = 0,
    this.isCombined = false,
    this.othersValue = 0.0,
    this.combined,
    this.splits = const [],
    required this.deliveryDate,
    this.deliveredDate = '',
    this.isDelivered = false,
    this.deliveryOutcomeStatus = 'in_progress',
    this.deliveryOutcomeLabel = '',
    this.daysRemaining = 999,
    this.daysRemainingBadge = '',
    this.daysRemainingUrgency = 'safe',
    required this.status,
    required this.progressPercent,
    required this.team,
    this.latestProgress = '',
    this.tips = 0.0,
    required this.createdAt,
    this.assignees = const [],
    this.notes = const [],
    this.extensionCount = 0,
    this.extensionDays = 0,
    this.extensionReason = '',
    this.deliveredWithoutExtension = false,
    this.isOntime = false,
    this.tipDate = '',
    this.clientReviewed = 'no',
    this.starRating = 0,
    this.reviewFeedback = '',
    this.projectSheetLink = '',
    this.orderSource = 'Query',
    this.orderLink = '',
    this.isCarryForward = false,
    this.carryForwardDays = 0,
  });

  factory ProjectItem.fromJson(Map<String, dynamic> json) {
    final double gross = double.tryParse(
      json['order_value']?.toString() ??
      json['gross_order_value']?.toString() ??
      json['price']?.toString() ??
      '0',
    ) ?? 0.0;

    // Single Source of Truth Financial Formula: Net Revenue = Gross * 0.80
    final double net = json['net_value'] != null
        ? (double.tryParse(json['net_value'].toString()) ?? (gross * 0.8))
        : (json['net_delivery_value'] != null
            ? (double.tryParse(json['net_delivery_value'].toString()) ?? (gross * 0.8))
            : (gross * 0.8));

    final double totalVal = double.tryParse(json['order_total_value']?.toString() ?? '0') ?? 0.0;
    final bool combinedFlag = json['is_combined'] == true || (totalVal > 0 && (totalVal - net) > 0.009);

    CombinedSummary? parsedCombined;
    if (json['combined'] is Map) {
      parsedCombined = CombinedSummary.fromJson(Map<String, dynamic>.from(json['combined']));
    }

    List<ProjectSplit> parsedSplits = [];
    if (parsedCombined != null && parsedCombined.splits.isNotEmpty) {
      parsedSplits = parsedCombined.splits;
    } else if (json['splits'] is List) {
      parsedSplits = (json['splits'] as List)
          .map((item) => ProjectSplit.fromJson(Map<String, dynamic>.from(item)))
          .toList();
    }

    List<ProjectAssignee> parsedAssignees = [];
    final rawAssignees = json['assigned_members'] ?? json['assignees'] ?? json['assignee_shares'];
    if (rawAssignees is List) {
      parsedAssignees = rawAssignees
          .map((item) => ProjectAssignee.fromJson(Map<String, dynamic>.from(item)))
          .toList();
    }

    List<ProjectNote> parsedNotes = [];
    if (json['notes'] is List) {
      parsedNotes = (json['notes'] as List)
          .map((item) => ProjectNote.fromJson(Map<String, dynamic>.from(item)))
          .toList();
    }

    // Days remaining & delivery outcome
    String outcomeStatus = 'in_progress';
    String outcomeLabel = '';
    if (json['delivery_outcome'] is Map) {
      outcomeStatus = (json['delivery_outcome']['status'] ?? 'in_progress').toString();
      outcomeLabel = (json['delivery_outcome']['label'] ?? '').toString();
    }

    int daysRem = 999;
    String daysBadge = '';
    String daysUrgency = 'safe';
    if (json['days_remaining'] is Map) {
      daysRem = int.tryParse(json['days_remaining']['days']?.toString() ?? '') ?? 999;
      daysBadge = (json['days_remaining']['short_badge'] ?? json['days_remaining']['label'] ?? '').toString();
      daysUrgency = (json['days_remaining']['urgency'] ?? 'safe').toString();
    }

    return ProjectItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      orderId: (json['order_id'] ?? '#FO${json['id']}').toString(),
      fiverrProfileId: json['fiverr_profile_id'] != null
          ? int.tryParse(json['fiverr_profile_id'].toString())
          : null,
      profileName: (json['profile_name'] ?? json['fiverr_profile_name'] ?? 'Agency Profile').toString(),
      buyerUsername: (json['buyer_username'] ?? json['client_name'] ?? json['client'] ?? 'Client').toString(),
      title: (json['title'] ?? json['project_title'] ?? 'WordPress Project').toString(),
      orderValue: gross,
      netValue: net,
      orderTotalValue: totalVal > 0 ? totalVal : gross,
      orderType: (json['order_type'] ?? 'standard').toString(),
      subscriptionStatus: (json['subscription_status'] ?? '').toString(),
      subscriptionNextDate: json['subscription_next_date']?.toString(),
      subscriptionTermDays: int.tryParse(json['subscription_term_days']?.toString() ?? '7') ?? 7,
      subscriptionParentId: int.tryParse(json['subscription_parent_id']?.toString() ?? '0') ?? 0,
      isCombined: combinedFlag,
      othersValue: double.tryParse(json['others_value']?.toString() ?? '0') ?? (combinedFlag ? (totalVal - net) : 0.0),
      combined: parsedCombined,
      splits: parsedSplits,
      deliveryDate: (json['delivery_date'] ?? json['deadline'] ?? '').toString(),
      deliveredDate: (json['delivered_date'] ?? '').toString(),
      isDelivered: json['is_delivered'] == true || ['delivery_accepted', 'delivery_submitted', 'completed'].contains((json['status'] ?? '').toString().toLowerCase()),
      deliveryOutcomeStatus: outcomeStatus,
      deliveryOutcomeLabel: outcomeLabel,
      daysRemaining: daysRem,
      daysRemainingBadge: daysBadge,
      daysRemainingUrgency: daysUrgency,
      status: (json['status'] ?? 'nra').toString(),
      progressPercent: int.tryParse(json['progress_percent']?.toString() ?? json['progress']?.toString() ?? '0') ?? 0,
      team: (json['team_name'] ?? json['team'] ?? '').toString(),
      latestProgress: (json['latest_progress'] ?? json['project_notes'] ?? '').toString(),
      tips: double.tryParse(json['tip_amount']?.toString() ?? json['tips']?.toString() ?? '0') ?? 0.0,
      createdAt: (json['created_at'] ?? '').toString(),
      assignees: parsedAssignees,
      notes: parsedNotes,
      extensionCount: int.tryParse(json['extension_count']?.toString() ?? '0') ?? 0,
      extensionDays: int.tryParse(json['extension_days']?.toString() ?? '0') ?? 0,
      extensionReason: (json['extension_reason'] ?? '').toString(),
      deliveredWithoutExtension: (() {
        final extCnt = int.tryParse(json['extension_count']?.toString() ?? '0') ?? 0;
        final rawSt = (json['status'] ?? 'nra').toString().toLowerCase();
        return extCnt == 0 && ['delivery_accepted', 'completed'].contains(rawSt);
      })(),
      isOntime: (json['is_ontime'] != null)
          ? (int.tryParse(json['is_ontime'].toString()) == 1)
          : ((int.tryParse(json['extension_count']?.toString() ?? '0') ?? 0) == 0 && ['delivery_accepted', 'completed', 'delivered'].contains((json['status'] ?? 'nra').toString().toLowerCase())),
      tipDate: (json['tip_date'] ?? '').toString(),
      clientReviewed: (json['client_reviewed'] ?? 'no').toString(),
      starRating: int.tryParse(json['star_rating']?.toString() ?? '0') ?? 0,
      reviewFeedback: (json['review_feedback'] ?? '').toString(),
      projectSheetLink: (json['project_sheet_link'] ?? json['sheet_link'] ?? '').toString(),
      orderSource: (json['order_source'] ?? 'Query').toString(),
      orderLink: (json['order_link'] ?? '').toString(),
      isCarryForward: json['is_carry_forward'] == true || json['is_carry_forward'] == 1 || json['is_carry_forward'] == '1',
      carryForwardDays: int.tryParse(json['carry_forward_days']?.toString() ?? '0') ?? 0,
    );
  }

  bool get isActive => !['delivery_accepted', 'completed', 'delivered', 'cancelled'].contains(status.toLowerCase());

  bool get isLate => isActive && (daysRemainingUrgency == 'danger' || daysRemainingBadge.toLowerCase().contains('late'));

  bool get isShortDeadline => isActive && !isLate && (daysRemainingUrgency == 'warning' || daysRemainingBadge.toLowerCase().contains('due today') || daysRemainingBadge.toLowerCase().contains('1d left') || daysRemainingBadge.toLowerCase().contains('2d left'));

  String get statusLabel {
    switch (status.toLowerCase()) {
      case 'nra':
        return 'Not Started (NRA)';
      case 'wip':
        return 'In Progress (WIP)';
      case 'delivery_submitted':
        return 'Delivery Submitted';
      case 'revision':
        return 'In Revision';
      case 'delivery_accepted':
        return 'Delivery Accepted';
      case 'completed':
        return 'Completed';
      case 'cancelled':
        return 'Cancelled';
      default:
        return status.toUpperCase();
    }
  }

  bool get isCompleted => ['delivery_accepted', 'completed'].contains(status.toLowerCase());
  bool get isSubmitted => status.toLowerCase() == 'delivery_submitted';
  bool get isCancelled => status.toLowerCase() == 'cancelled';
  bool get isRepeatClient => orderSource.toLowerCase().contains('repeat');
}
