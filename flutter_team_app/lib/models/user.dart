class AuthUser {
  final int id;
  final String name;
  final String email;
  final String avatar;
  final String role;
  final String roleLabel;
  final String team;
  final bool isAdmin;
  final bool isLeader;
  final String badgeId;
  final String callSign;
  final String professionalTitle;

  AuthUser({
    required this.id,
    required this.name,
    required this.email,
    required this.avatar,
    required this.role,
    required this.roleLabel,
    required this.team,
    required this.isAdmin,
    required this.isLeader,
    required this.badgeId,
    required this.callSign,
    required this.professionalTitle,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      avatar: json['avatar'] ?? '',
      role: json['role'] ?? 'member',
      roleLabel: json['role_label'] ?? json['role'] ?? 'Specialist',
      team: json['team'] ?? '',
      isAdmin: json['is_admin'] == true || json['is_admin'] == 1 || json['role'] == 'admin',
      isLeader: json['is_leader'] == true || json['is_leader'] == 1 || json['role'] == 'leader' || json['role'] == 'co_leader' || json['role'] == 'admin',
      badgeId: json['badge_id'] ?? json['custom_id'] ?? '#${json['id']}',
      callSign: json['call_sign'] ?? json['name'] ?? '',
      professionalTitle: json['professional_title'] ?? 'Specialist',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'avatar': avatar,
      'role': role,
      'role_label': roleLabel,
      'team': team,
      'is_admin': isAdmin,
      'is_leader': isLeader,
      'badge_id': badgeId,
      'call_sign': callSign,
      'professional_title': professionalTitle,
    };
  }

  bool get isExecutive =>
      role == 'executive' ||
      role == 'ftm_executive' ||
      role == 'deputy' ||
      role == 'ftm_deputy' ||
      role == 'management' ||
      role == 'ftm_management';

  bool get isDeputy => isExecutive;

  bool get isOperationsManager =>
      (isAdmin || role == 'admin' || role == 'ftm_admin') && !isExecutive;

  bool get isSquadLeader =>
      (isLeader || role == 'leader' || role == 'co_leader' || role == 'ftm_leader') &&
      !isOperationsManager &&
      !isExecutive;

  bool get isSpecialist =>
      !isOperationsManager && !isSquadLeader && !isExecutive;

  // View Permissions (Executive & Ops Manager have universal panoramic visibility)
  bool get canViewAllProjects => isExecutive || isOperationsManager;
  bool get canViewAllTeams => isExecutive || isOperationsManager;
  bool get canViewAllDailyReports => isExecutive || isOperationsManager;
  bool get canViewAllMonthlyReports => isExecutive || isOperationsManager;
  bool get canViewAllMembers => isExecutive || isOperationsManager;
  bool get canViewLeaderboard => true;

  // Mutation Permissions (Executive is strictly read-only)
  bool get isViewOnly => isExecutive;
  bool get canAddProjects => isOperationsManager || isSquadLeader;
  bool get canEditProjects => isOperationsManager || isSquadLeader;
  bool get canDeleteProjects => isOperationsManager;
  bool get canReviewStandups => isOperationsManager || isSquadLeader;
  bool get canLogStandup => !isExecutive;
  bool get canManageProfiles => isOperationsManager;
  bool get canCreateNotice => isOperationsManager;
  bool get canAddMilestones => isOperationsManager;
}

class Specialist {
  final int id;
  final String name;
  final String email;
  final String avatar;
  final String team;
  final String teamRole;
  final String roleLabel;
  final String customId;
  final String callSign;
  final String professionalTitle;
  final String weeklyDayOff;
  final double annualQuota;
  final double leaveAdjustment;
  final String quotaNote;
  final String joinedDate;
  final String status;
  final int totalProjects;
  final int activeProjects;
  final int deliveredProjects;
  final int wipProjects;
  final int revisionProjects;
  final double netDelivered;
  final double activeWipValue;
  final double totalTips;

  Specialist({
    required this.id,
    required this.name,
    required this.email,
    required this.avatar,
    required this.team,
    required this.teamRole,
    required this.roleLabel,
    required this.customId,
    required this.callSign,
    required this.professionalTitle,
    required this.totalProjects,
    required this.activeProjects,
    required this.deliveredProjects,
    required this.wipProjects,
    required this.revisionProjects,
    this.netDelivered = 0.0,
    this.activeWipValue = 0.0,
    this.totalTips = 0.0,
    this.weeklyDayOff = 'Sunday',
    this.annualQuota = 28.0,
    this.leaveAdjustment = 0.0,
    this.quotaNote = '',
    this.joinedDate = '',
    this.status = 'active',
  });

  factory Specialist.fromJson(Map<String, dynamic> json) {
    return Specialist(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      avatar: json['avatar'] ?? '',
      team: json['team'] ?? '',
      teamRole: json['team_role'] ?? json['role'] ?? 'member',
      roleLabel: json['role_label'] ?? 'Specialist',
      customId: json['custom_id'] ?? '#${json['id']}',
      callSign: json['call_sign'] ?? json['name'] ?? '',
      professionalTitle: json['professional_title'] ?? 'Specialist',
      totalProjects: int.tryParse(json['total_projects']?.toString() ?? '0') ?? 0,
      activeProjects: int.tryParse(json['active_projects']?.toString() ?? '0') ?? 0,
      deliveredProjects: int.tryParse(json['delivered_projects']?.toString() ?? '0') ?? 0,
      wipProjects: int.tryParse(json['wip_projects']?.toString() ?? '0') ?? 0,
      revisionProjects: int.tryParse(json['revision_projects']?.toString() ?? '0') ?? 0,
      netDelivered: double.tryParse(json['net_delivered']?.toString() ?? '0') ?? 0.0,
      activeWipValue: double.tryParse(json['active_wip_value']?.toString() ?? '0') ?? 0.0,
      totalTips: double.tryParse(json['total_tips']?.toString() ?? '0') ?? 0.0,
      weeklyDayOff: (json['weekly_day_off'] ?? 'Sunday').toString(),
      annualQuota: double.tryParse(json['annual_quota']?.toString() ?? '28') ?? 28.0,
      leaveAdjustment: double.tryParse(json['leave_adjustment']?.toString() ?? '0') ?? 0.0,
      quotaNote: (json['quota_note'] ?? '').toString(),
      joinedDate: (json['joined_date'] ?? '').toString(),
      status: (json['status'] ?? 'active').toString(),
    );
  }
}

class MemberMilestone {
  final int id;
  final String title;
  final String milestoneDate;
  final String description;
  final String badgeType;

  MemberMilestone({
    required this.id,
    required this.title,
    required this.milestoneDate,
    required this.description,
    this.badgeType = 'milestone',
  });

  factory MemberMilestone.fromJson(Map<String, dynamic> json) {
    return MemberMilestone(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      title: json['title'] ?? '',
      milestoneDate: json['milestone_date'] ?? json['date'] ?? '',
      description: json['description'] ?? '',
      badgeType: json['badge_type'] ?? 'milestone',
    );
  }
}
