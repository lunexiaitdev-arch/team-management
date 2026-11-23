class FiverrProfile {
  final int id;
  final String name;
  final String url;
  final String status;
  final int totalProjects;
  final int activeProjects;
  final int completedProjects;

  FiverrProfile({
    required this.id,
    required this.name,
    required this.url,
    required this.status,
    required this.totalProjects,
    required this.activeProjects,
    required this.completedProjects,
  });

  factory FiverrProfile.fromJson(Map<String, dynamic> json) {
    return FiverrProfile(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name'] ?? '',
      url: json['url'] ?? '',
      status: json['status'] ?? 'active',
      totalProjects: int.tryParse(json['total_projects']?.toString() ?? '0') ?? 0,
      activeProjects: int.tryParse(json['active_projects']?.toString() ?? '0') ?? 0,
      completedProjects: int.tryParse(json['completed_projects']?.toString() ?? '0') ?? 0,
    );
  }

  bool get isActive => status.toLowerCase() == 'active';
}
