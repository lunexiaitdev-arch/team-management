class ExecutiveStats {
  final int total;
  final int nra;
  final int wip;
  final int deliverySubmitted;
  final int revision;
  final int deliveryAccepted;
  final int completed;
  final int cancelled;
  final double cancelledNet;
  final double totalGrossValue;
  final double totalNetRevenue;
  final double activeWipValue;
  final double deliverySubmittedValue;
  final double activeRevisionValue;
  final double totalTips;
  final int repeatOrdersCount;
  final double repeatGrossRevenue;
  final double repeatNetRevenue;
  final int activeSpecialists;
  final int totalSpecialists;
  final int carryForwardCount;
  final double carryForwardVolume;
  final int newSalesCount;
  final double newSalesVolume;
  final int totalAssignedCount;
  final double totalAssignedVolume;

  ExecutiveStats({
    required this.total,
    required this.nra,
    required this.wip,
    required this.deliverySubmitted,
    required this.revision,
    required this.deliveryAccepted,
    required this.completed,
    required this.cancelled,
    this.cancelledNet = 0.0,
    required this.totalGrossValue,
    required this.totalNetRevenue,
    required this.activeWipValue,
    this.deliverySubmittedValue = 0.0,
    this.activeRevisionValue = 0.0,
    required this.totalTips,
    this.repeatOrdersCount = 0,
    this.repeatGrossRevenue = 0.0,
    this.repeatNetRevenue = 0.0,
    this.activeSpecialists = 0,
    this.totalSpecialists = 0,
    this.carryForwardCount = 0,
    this.carryForwardVolume = 0.0,
    this.newSalesCount = 0,
    this.newSalesVolume = 0.0,
    this.totalAssignedCount = 0,
    this.totalAssignedVolume = 0.0,
  });

  factory ExecutiveStats.fromJson(Map<String, dynamic> json) {
    final fin = json['financials'] is Map ? (json['financials'] as Map) : {};
    // Note: backend compute_period_totals() already includes delivered_tips in total_delivery_net
    final double netFromFin = fin['total_delivery_net'] != null
        ? (double.tryParse(fin['total_delivery_net'].toString()) ?? 0.0)
        : (double.tryParse(fin['total_work_assigned']?.toString() ?? '0') ?? 0.0);

    final double grossFromFin = fin['total_work_assigned'] != null
        ? ((double.tryParse(fin['total_work_assigned'].toString()) ?? 0.0) / 0.8)
        : (netFromFin > 0 ? netFromFin / 0.8 : 0.0);

    final double gross = (double.tryParse(json['total_gross_value']?.toString() ?? json['gross_revenue']?.toString() ?? '0') ?? 0.0) > 0
        ? (double.tryParse(json['total_gross_value']?.toString() ?? json['gross_revenue']?.toString() ?? '0') ?? 0.0)
        : grossFromFin;

    final double net = json['total_net_revenue'] != null && (double.tryParse(json['total_net_revenue'].toString()) ?? 0.0) > 0
        ? (double.tryParse(json['total_net_revenue'].toString()) ?? 0.0)
        : (netFromFin > 0 ? netFromFin : (gross * 0.8));

    return ExecutiveStats(
      total: int.tryParse(json['total']?.toString() ?? '0') ?? 0,
      nra: int.tryParse(json['nra']?.toString() ?? '0') ?? 0,
      wip: int.tryParse(json['wip']?.toString() ?? '0') ?? 0,
      deliverySubmitted: int.tryParse(json['delivery_submitted']?.toString() ?? '0') ?? 0,
      revision: int.tryParse(json['revision']?.toString() ?? '0') ?? 0,
      deliveryAccepted: int.tryParse(json['delivery_accepted']?.toString() ?? '0') ?? 0,
      completed: int.tryParse(json['completed']?.toString() ?? '0') ?? 0,
      cancelled: int.tryParse(json['cancelled']?.toString() ?? fin['total_cancelled_orders']?.toString() ?? '0') ?? 0,
      cancelledNet: double.tryParse(fin['total_cancelled_net']?.toString() ?? json['cancelled_value']?.toString() ?? '0') ?? 0.0,
      totalGrossValue: gross,
      totalNetRevenue: net,
      activeWipValue: double.tryParse(json['active_wip_value']?.toString() ?? fin['total_wip_net']?.toString() ?? '0') ?? 0.0,
      deliverySubmittedValue: double.tryParse(json['delivery_submitted_value']?.toString() ?? fin['total_submitted_net']?.toString() ?? '0') ?? 0.0,
      activeRevisionValue: double.tryParse(json['active_revision_value']?.toString() ?? fin['total_revision_net']?.toString() ?? '0') ?? 0.0,
      totalTips: double.tryParse(json['total_tips']?.toString() ?? fin['total_tips_net']?.toString() ?? '0') ?? 0.0,
      repeatOrdersCount: int.tryParse(json['repeat_orders_count']?.toString() ?? '0') ?? 0,
      repeatGrossRevenue: double.tryParse(json['repeat_gross_revenue']?.toString() ?? '0') ?? 0.0,
      repeatNetRevenue: double.tryParse(json['repeat_net_revenue']?.toString() ?? '0') ?? 0.0,
      activeSpecialists: int.tryParse(json['active_specialists']?.toString() ?? '0') ?? 0,
      totalSpecialists: int.tryParse(json['total_specialists']?.toString() ?? '0') ?? 0,
      carryForwardCount: int.tryParse(json['carry_forward_count']?.toString() ?? '0') ?? 0,
      carryForwardVolume: double.tryParse(json['carry_forward_volume']?.toString() ?? '0') ?? 0.0,
      newSalesCount: int.tryParse(json['new_sales_count']?.toString() ?? '0') ?? 0,
      newSalesVolume: double.tryParse(json['new_sales_volume']?.toString() ?? '0') ?? 0.0,
      totalAssignedCount: int.tryParse(json['total_assigned_count']?.toString() ?? '0') ?? 0,
      totalAssignedVolume: double.tryParse(json['total_assigned_volume']?.toString() ?? '0') ?? 0.0,
    );
  }

  int get activeOrdersCount => nra + wip + revision;
  int get totalCompletedCount => deliveryAccepted + completed;
}
