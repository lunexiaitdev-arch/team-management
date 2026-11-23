class MonthlyReportRow {
  final int userId;
  final String name;
  final String avatar;
  final String callSign;
  final String role;
  final String team;
  final String customId;

  // The 7 Required Operational Metrics:
  final int wipCount;
  final double wipVal;
  final int submittedCount;
  final double submittedVal;
  final int nraCount;
  final double nraVal;
  final int deliveryCount;
  final double deliveryVal;
  final int cancelledCount;
  final double cancelledVal;
  final int newAssignedCount;
  final double newAssignedVal;
  final int carryForwardCount;
  final double carryForwardVal;
  final int totalAssignedCount;
  final double totalAssignedVal;

  // Auxiliary metrics:
  final double deliveredGross;
  final double deliveredNet;
  final double totalTips;
  final double totalAddons;
  final double totalRealizedNet;
  final int stars5;
  final int onTimeCount;
  final int extensionCount;
  final int extensionDays;
  final int lateCount;
  final int tipCount;
  final int repeatCount;
  final double repeatVal;
  final double avgRating;
  final int reviewCount;

  MonthlyReportRow({
    required this.userId,
    required this.name,
    required this.avatar,
    required this.callSign,
    required this.role,
    required this.team,
    required this.customId,
    required this.wipCount,
    required this.wipVal,
    this.submittedCount = 0,
    this.submittedVal = 0.0,
    required this.nraCount,
    required this.nraVal,
    required this.deliveryCount,
    required this.deliveryVal,
    required this.cancelledCount,
    required this.cancelledVal,
    required this.newAssignedCount,
    required this.newAssignedVal,
    required this.carryForwardCount,
    required this.carryForwardVal,
    required this.totalAssignedCount,
    required this.totalAssignedVal,
    required this.deliveredGross,
    required this.deliveredNet,
    required this.totalTips,
    required this.totalAddons,
    required this.totalRealizedNet,
    this.stars5 = 0,
    this.onTimeCount = 0,
    this.extensionCount = 0,
    this.extensionDays = 0,
    this.lateCount = 0,
    this.tipCount = 0,
    this.repeatCount = 0,
    this.repeatVal = 0.0,
    this.avgRating = 5.0,
    this.reviewCount = 0,
  });

  factory MonthlyReportRow.fromJson(Map<String, dynamic> json) {
    final double net = double.tryParse(
      json['delivered_net']?.toString() ??
      json['total_delivery']?.toString() ??
      json['delivery']?.toString() ??
      json['net_revenue']?.toString() ??
      '0',
    ) ?? 0.0;

    final double? parsedExplicitGross = (json['delivered_gross'] != null || json['total_delivery_gross'] != null || json['gross'] != null)
        ? double.tryParse(
            json['delivered_gross']?.toString() ??
            json['total_delivery_gross']?.toString() ??
            json['gross']?.toString() ??
            '',
          )
        : null;
    final double gross = (parsedExplicitGross != null && parsedExplicitGross > 0)
        ? parsedExplicitGross
        : (net > 0 ? (net / 0.8) : 0.0);

    final double tips = double.tryParse(json['total_tips']?.toString() ?? json['tips']?.toString() ?? '0') ?? 0.0;
    final double addons = double.tryParse(json['total_addons']?.toString() ?? '0') ?? 0.0;

    final String memberName = (json['member_name'] ?? json['name'] ?? json['display_name'] ?? '').toString();
    final String rawCustomId = (json['member_custom_id'] ?? json['custom_id'] ?? json['badge_id'] ?? '').toString().replaceAll('#', '');
    final String teamName = (json['team_name'] ?? json['team'] ?? '').toString();
    final String callSign = (json['call_sign'] ?? memberName).toString();

    // 1. Total WIP
    final int wipCnt = int.tryParse(json['wip_count']?.toString() ?? json['active_wip_count']?.toString() ?? '0') ?? 0;
    final double wipValue = double.tryParse(
      json['wip_val']?.toString() ??
      json['total_wip']?.toString() ??
      json['active_wip_net']?.toString() ??
      '0',
    ) ?? 0.0;

    // Delivery Submitted
    final int submCnt = int.tryParse(json['submitted_count']?.toString() ?? '0') ?? 0;
    final double submValue = double.tryParse(json['submitted_val']?.toString() ?? json['total_submitted']?.toString() ?? '0') ?? 0.0;

    // 2. NRA
    final int nraCnt = int.tryParse(json['nra_count']?.toString() ?? json['nra']?.toString() ?? '0') ?? 0;
    final double nraValue = double.tryParse(json['nra_val']?.toString() ?? '0') ?? 0.0;

    // 3. Delivery
    final int delCnt = int.tryParse(
      json['delivery_count']?.toString() ??
      json['total_completed']?.toString() ??
      json['deliveries']?.toString() ??
      '0',
    ) ?? 0;

    // 4. Cancelled
    final int cancCnt = int.tryParse(json['cancelled_count']?.toString() ?? json['total_cancel']?.toString() ?? '0') ?? 0;
    final double cancValue = double.tryParse(json['cancelled_val']?.toString() ?? json['cancelled']?.toString() ?? '0') ?? 0.0;

    // 5. New Assigned (Current Month)
    final int newCnt = int.tryParse(json['new_assigned_count']?.toString() ?? json['new_assigned']?.toString() ?? '0') ?? 0;
    final double newValue = double.tryParse(json['new_assigned_val']?.toString() ?? '0') ?? 0.0;

    // 6. Carry Forward (From Previous Months)
    final int carCnt = int.tryParse(json['carry_forward_count']?.toString() ?? json['carry_forward']?.toString() ?? '0') ?? 0;
    final double carValue = double.tryParse(json['carry_forward_val']?.toString() ?? '0') ?? 0.0;

    // 7. Total Assigned = New Assigned + Carry Forward + Tips
    final int totAssignedCnt = int.tryParse(json['total_assigned_count']?.toString() ?? json['total_assigned']?.toString() ?? '') ?? (newCnt + carCnt);
    final double totAssignedVal = double.tryParse(json['total_assigned_val']?.toString() ?? '') ?? (newValue + carValue + tips);

    return MonthlyReportRow(
      userId: int.tryParse(json['user_id']?.toString() ?? json['id']?.toString() ?? '0') ?? 0,
      name: memberName,
      avatar: (json['avatar'] ?? json['avatar_url'] ?? '').toString(),
      callSign: callSign,
      role: (json['role'] ?? json['role_label'] ?? 'Specialist').toString(),
      team: teamName,
      customId: rawCustomId.isNotEmpty && rawCustomId != 'null' ? '#$rawCustomId' : (memberName.isNotEmpty ? memberName : ''),
      wipCount: wipCnt,
      wipVal: wipValue,
      submittedCount: submCnt,
      submittedVal: submValue,
      nraCount: nraCnt,
      nraVal: nraValue,
      deliveryCount: delCnt,
      deliveryVal: net,
      cancelledCount: cancCnt,
      cancelledVal: cancValue,
      newAssignedCount: newCnt,
      newAssignedVal: newValue,
      carryForwardCount: carCnt,
      carryForwardVal: carValue,
      totalAssignedCount: totAssignedCnt,
      totalAssignedVal: totAssignedVal,
      deliveredGross: gross,
      deliveredNet: net,
      totalTips: tips,
      totalAddons: addons,
      totalRealizedNet: net + tips,
      stars5: int.tryParse(json['stars_5']?.toString() ?? '0') ?? 0,
      onTimeCount: int.tryParse(json['delivered_without_extension']?.toString() ?? json['on_time_count']?.toString() ?? '0') ?? 0,
      extensionCount: int.tryParse(json['extension_count']?.toString() ?? '0') ?? 0,
      extensionDays: int.tryParse(json['extension_days']?.toString() ?? '0') ?? 0,
      lateCount: int.tryParse(json['late_count']?.toString() ?? '0') ?? 0,
      tipCount: int.tryParse(json['tip_count']?.toString() ?? '0') ?? 0,
      repeatCount: int.tryParse(json['repeat_count']?.toString() ?? json['repeat_orders']?.toString() ?? '0') ?? 0,
      repeatVal: double.tryParse(json['repeat_val']?.toString() ?? json['repeat_net_value']?.toString() ?? '0') ?? 0.0,
      avgRating: double.tryParse(json['avg_rating']?.toString() ?? '5.0') ?? 5.0,
      reviewCount: int.tryParse(json['review_count']?.toString() ?? '0') ?? 0,
    );
  }
}

class TeamBreakdownItem {
  final String teamName;
  final int memberCount;
  final int wipCount;
  final double wipVal;
  final int submittedCount;
  final double submittedVal;
  final int nraCount;
  final double nraVal;
  final int deliveryCount;
  final double deliveryVal;
  final int cancelledCount;
  final double cancelledVal;
  final int newAssignedCount;
  final double newAssignedVal;
  final int carryForwardCount;
  final double carryForwardVal;
  final int totalAssignedCount;
  final double totalAssignedVal;
  final int repeatCount;
  final double repeatVal;

  TeamBreakdownItem({
    required this.teamName,
    required this.memberCount,
    required this.wipCount,
    required this.wipVal,
    this.submittedCount = 0,
    this.submittedVal = 0.0,
    required this.nraCount,
    required this.nraVal,
    required this.deliveryCount,
    required this.deliveryVal,
    required this.cancelledCount,
    required this.cancelledVal,
    required this.newAssignedCount,
    required this.newAssignedVal,
    required this.carryForwardCount,
    required this.carryForwardVal,
    required this.totalAssignedCount,
    required this.totalAssignedVal,
    this.repeatCount = 0,
    this.repeatVal = 0.0,
  });

  factory TeamBreakdownItem.fromJson(Map<String, dynamic> json) {
    return TeamBreakdownItem(
      teamName: (json['team_name'] ?? json['team'] ?? '').toString(),
      memberCount: int.tryParse(json['members_count']?.toString() ?? json['member_count']?.toString() ?? '0') ?? 0,
      wipCount: int.tryParse(json['wip_count']?.toString() ?? '0') ?? 0,
      wipVal: double.tryParse(json['wip_val']?.toString() ?? '0') ?? 0.0,
      submittedCount: int.tryParse(json['submitted_count']?.toString() ?? '0') ?? 0,
      submittedVal: double.tryParse(json['submitted_val']?.toString() ?? '0') ?? 0.0,
      nraCount: int.tryParse(json['nra_count']?.toString() ?? '0') ?? 0,
      nraVal: double.tryParse(json['nra_val']?.toString() ?? '0') ?? 0.0,
      deliveryCount: int.tryParse(json['delivery_count']?.toString() ?? '0') ?? 0,
      deliveryVal: double.tryParse(json['delivery_val']?.toString() ?? '0') ?? 0.0,
      cancelledCount: int.tryParse(json['cancelled_count']?.toString() ?? '0') ?? 0,
      cancelledVal: double.tryParse(json['cancelled_val']?.toString() ?? '0') ?? 0.0,
      newAssignedCount: int.tryParse(json['new_assigned_count']?.toString() ?? '0') ?? 0,
      newAssignedVal: double.tryParse(json['new_assigned_val']?.toString() ?? '0') ?? 0.0,
      carryForwardCount: int.tryParse(json['carry_forward_count']?.toString() ?? '0') ?? 0,
      carryForwardVal: double.tryParse(json['carry_forward_val']?.toString() ?? '0') ?? 0.0,
      totalAssignedCount: int.tryParse(json['total_assigned_count']?.toString() ?? '0') ?? 0,
      totalAssignedVal: double.tryParse(json['total_assigned_val']?.toString() ?? '0') ?? 0.0,
      repeatCount: int.tryParse(json['repeat_count']?.toString() ?? '0') ?? 0,
      repeatVal: double.tryParse(json['repeat_val']?.toString() ?? '0') ?? 0.0,
    );
  }
}

class MonthlyReportData {
  final String reportingMonth;
  final bool isHistoricalArchive;

  // The 7 Consolidated Totals:
  final int totalWipCount;
  final double totalWipVal;
  final int totalSubmittedCount;
  final double totalSubmittedVal;
  final int totalNraCount;
  final double totalNraVal;
  final int totalDeliveryCount;
  final double totalDeliveryVal;
  final int totalCancelledCount;
  final double totalCancelledVal;
  final int totalNewAssignedCount;
  final double totalNewAssignedVal;
  final int totalCarryForwardCount;
  final double totalCarryForwardVal;
  final int totalAssignedCount;
  final double totalAssignedVal;

  final double totalGross;
  final double totalNet;
  final double totalTips;
  final double totalAddons;
  final int totalDeliveries;
  final int totalRepeatCount;
  final double totalRepeatVal;
  final List<MonthlyReportRow> rows;
  final List<TeamBreakdownItem> teamBreakdown;

  MonthlyReportData({
    required this.reportingMonth,
    required this.isHistoricalArchive,
    required this.totalWipCount,
    required this.totalWipVal,
    this.totalSubmittedCount = 0,
    this.totalSubmittedVal = 0.0,
    required this.totalNraCount,
    required this.totalNraVal,
    required this.totalDeliveryCount,
    required this.totalDeliveryVal,
    required this.totalCancelledCount,
    required this.totalCancelledVal,
    required this.totalNewAssignedCount,
    required this.totalNewAssignedVal,
    required this.totalCarryForwardCount,
    required this.totalCarryForwardVal,
    required this.totalAssignedCount,
    required this.totalAssignedVal,
    required this.totalGross,
    required this.totalNet,
    required this.totalTips,
    required this.totalAddons,
    required this.totalDeliveries,
    this.totalRepeatCount = 0,
    this.totalRepeatVal = 0.0,
    required this.rows,
    required this.teamBreakdown,
  });

  factory MonthlyReportData.fromJson(Map<String, dynamic> json) {
    final totals = json['totals'] is Map ? json['totals'] : {};

    final double net = double.tryParse(
      totals['total_delivery']?.toString() ??
      totals['total_net']?.toString() ??
      totals['delivery']?.toString() ??
      totals['total_delivery_net']?.toString() ??
      '0',
    ) ?? 0.0;

    final double? parsedExplicitGross = (totals['total_gross'] != null || totals['gross'] != null || totals['total_delivery_gross'] != null)
        ? double.tryParse(
            totals['total_gross']?.toString() ??
            totals['gross']?.toString() ??
            totals['total_delivery_gross']?.toString() ??
            '',
          )
        : null;
    final double gross = (parsedExplicitGross != null && parsedExplicitGross > 0)
        ? parsedExplicitGross
        : (net > 0 ? (net / 0.8) : 0.0);

    List<MonthlyReportRow> parsedRows = [];
    if (json['rows'] is List) {
      parsedRows = (json['rows'] as List)
          .map((r) => MonthlyReportRow.fromJson(Map<String, dynamic>.from(r)))
          .toList();
    }

    List<TeamBreakdownItem> parsedTeams = [];
    if (json['team_breakdown'] is List) {
      parsedTeams = (json['team_breakdown'] as List)
          .map((t) => TeamBreakdownItem.fromJson(Map<String, dynamic>.from(t)))
          .toList();
    }

    // 1. Total WIP
    final int wipCnt = int.tryParse(totals['wip_count']?.toString() ?? totals['total_wip_count']?.toString() ?? '0') ??
        parsedRows.fold(0, (sum, r) => sum + r.wipCount);
    final double wipVal = double.tryParse(totals['total_wip']?.toString() ?? totals['wip_val']?.toString() ?? '0') ??
        parsedRows.fold(0.0, (sum, r) => sum + r.wipVal);

    // Delivery Submitted
    final int submCnt = int.tryParse(totals['submitted_count']?.toString() ?? totals['total_submitted_count']?.toString() ?? '') ??
        parsedRows.fold(0, (sum, r) => sum + r.submittedCount);
    final double submVal = double.tryParse(totals['total_submitted']?.toString() ?? totals['submitted_val']?.toString() ?? '') ??
        parsedRows.fold(0.0, (sum, r) => sum + r.submittedVal);

    // 2. NRA
    final int nraCnt = int.tryParse(totals['nra_count']?.toString() ?? totals['nra']?.toString() ?? '0') ??
        parsedRows.fold(0, (sum, r) => sum + r.nraCount);
    final double nraVal = double.tryParse(totals['nra_val']?.toString() ?? '0') ??
        parsedRows.fold(0.0, (sum, r) => sum + r.nraVal);

    // 3. Delivery
    final int delCnt = int.tryParse(totals['delivery_count']?.toString() ?? totals['total_completed']?.toString() ?? totals['total_deliveries']?.toString() ?? totals['deliveries']?.toString() ?? '0') ??
        parsedRows.fold(0, (sum, r) => sum + r.deliveryCount);

    // 4. Cancelled
    final int cancCnt = int.tryParse(totals['cancelled_count']?.toString() ?? totals['total_cancel']?.toString() ?? '0') ??
        parsedRows.fold(0, (sum, r) => sum + r.cancelledCount);
    final double cancVal = double.tryParse(totals['cancelled_val']?.toString() ?? totals['cancelled']?.toString() ?? '0') ??
        parsedRows.fold(0.0, (sum, r) => sum + r.cancelledVal);

    // 5. New Assigned
    final int newCnt = int.tryParse(totals['new_assigned_count']?.toString() ?? totals['new_assigned']?.toString() ?? '0') ??
        parsedRows.fold(0, (sum, r) => sum + r.newAssignedCount);
    final double newVal = double.tryParse(totals['new_assigned_val']?.toString() ?? '0') ??
        parsedRows.fold(0.0, (sum, r) => sum + r.newAssignedVal);

    // 6. Carry Forward
    final int carCnt = int.tryParse(totals['carry_forward_count']?.toString() ?? totals['carry_forward']?.toString() ?? '0') ??
        parsedRows.fold(0, (sum, r) => sum + r.carryForwardCount);
    final double carVal = double.tryParse(totals['carry_forward_val']?.toString() ?? '0') ??
        parsedRows.fold(0.0, (sum, r) => sum + r.carryForwardVal);

    // 7. Total Assigned
    final int totAssignedCnt = int.tryParse(totals['total_assigned_count']?.toString() ?? totals['total_assigned']?.toString() ?? '') ?? (newCnt + carCnt);
    final double totAssignedVal = double.tryParse(totals['total_assigned_val']?.toString() ?? '') ?? (newVal + carVal + (double.tryParse(totals['total_tips']?.toString() ?? totals['tips']?.toString() ?? '') ?? 0.0));

    return MonthlyReportData(
      reportingMonth: (json['reporting_month'] ?? json['month'] ?? '').toString(),
      isHistoricalArchive: json['is_historical_archive'] == true,
      totalWipCount: wipCnt,
      totalWipVal: wipVal,
      totalSubmittedCount: submCnt,
      totalSubmittedVal: submVal,
      totalNraCount: nraCnt,
      totalNraVal: nraVal,
      totalDeliveryCount: delCnt,
      totalDeliveryVal: net,
      totalCancelledCount: cancCnt,
      totalCancelledVal: cancVal,
      totalNewAssignedCount: newCnt,
      totalNewAssignedVal: newVal,
      totalCarryForwardCount: carCnt,
      totalCarryForwardVal: carVal,
      totalAssignedCount: totAssignedCnt,
      totalAssignedVal: totAssignedVal,
      totalGross: gross,
      totalNet: net,
      totalTips: double.tryParse(totals['tips']?.toString() ?? totals['total_tips']?.toString() ?? '0') ?? 0.0,
      totalAddons: double.tryParse(totals['addons']?.toString() ?? totals['total_addons']?.toString() ?? '0') ?? 0.0,
      totalDeliveries: delCnt,
      totalRepeatCount: int.tryParse(totals['repeat_count']?.toString() ?? totals['repeat_orders_count']?.toString() ?? '') ??
          parsedRows.fold(0, (sum, r) => sum + r.repeatCount),
      totalRepeatVal: double.tryParse(totals['repeat_val']?.toString() ?? totals['repeat_net_value']?.toString() ?? '') ??
          parsedRows.fold(0.0, (sum, r) => sum + r.repeatVal),
      rows: parsedRows,
      teamBreakdown: parsedTeams,
    );
  }
}
