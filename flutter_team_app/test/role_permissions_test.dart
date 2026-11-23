import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_team_app/models/user.dart';
import 'package:flutter_team_app/models/app_notification.dart';

void main() {
  group('Role Permissions and Executive Special Access Tests', () {
    test('Executive Role has full panoramic report viewing but strictly read-only mutation permissions', () {
      final executive = AuthUser(
        id: 10,
        name: 'Chief Executive',
        email: 'exec@fbinternationalbd.com',
        avatar: '',
        role: 'executive',
        roleLabel: 'Executive Management',
        team: 'Management',
        isAdmin: true,
        isLeader: true,
        badgeId: '#EXEC-01',
        callSign: 'CEO',
        professionalTitle: 'Executive Director',
      );

      // Role Checks
      expect(executive.isExecutive, isTrue);
      expect(executive.isOperationsManager, isFalse);
      expect(executive.isSquadLeader, isFalse);
      expect(executive.isSpecialist, isFalse);

      // Panoramic View Permissions
      expect(executive.canViewAllProjects, isTrue);
      expect(executive.canViewAllTeams, isTrue);
      expect(executive.canViewAllDailyReports, isTrue);
      expect(executive.canViewAllMonthlyReports, isTrue);
      expect(executive.canViewAllMembers, isTrue);
      expect(executive.canViewLeaderboard, isTrue);

      // Read-Only Mutation Restrictions
      expect(executive.isViewOnly, isTrue);
      expect(executive.canAddProjects, isFalse);
      expect(executive.canEditProjects, isFalse);
      expect(executive.canDeleteProjects, isFalse);
      expect(executive.canReviewStandups, isFalse);
      expect(executive.canLogStandup, isFalse);
      expect(executive.canManageProfiles, isFalse);
      expect(executive.canCreateNotice, isFalse);
    });

    test('Operations Manager has full CRUD access across projects and team management', () {
      final manager = AuthUser(
        id: 1,
        name: 'Operations Manager',
        email: 'manager@fbinternationalbd.com',
        avatar: '',
        role: 'admin',
        roleLabel: 'Operations Manager',
        team: 'Management',
        isAdmin: true,
        isLeader: true,
        badgeId: '#OPS-01',
        callSign: 'OpsLead',
        professionalTitle: 'Operations Manager',
      );

      expect(manager.isExecutive, isFalse);
      expect(manager.isOperationsManager, isTrue);
      expect(manager.isViewOnly, isFalse);
      expect(manager.canAddProjects, isTrue);
      expect(manager.canEditProjects, isTrue);
      expect(manager.canDeleteProjects, isTrue);
      expect(manager.canReviewStandups, isTrue);
      expect(manager.canManageProfiles, isTrue);
      expect(manager.canCreateNotice, isTrue);
      expect(manager.canViewAllProjects, isTrue);
      expect(manager.canViewAllMonthlyReports, isTrue);
    });

    test('Squad Leader can add and manage squad deliverables and review standups', () {
      final leader = AuthUser(
        id: 5,
        name: 'Squad Leader',
        email: 'leader@fbinternationalbd.com',
        avatar: '',
        role: 'leader',
        roleLabel: 'Team Leader',
        team: 'WpCraft',
        isAdmin: false,
        isLeader: true,
        badgeId: '#LEAD-05',
        callSign: 'CraftLead',
        professionalTitle: 'Squad Leader',
      );

      expect(leader.isExecutive, isFalse);
      expect(leader.isOperationsManager, isFalse);
      expect(leader.isSquadLeader, isTrue);
      expect(leader.isViewOnly, isFalse);
      expect(leader.canAddProjects, isTrue);
      expect(leader.canEditProjects, isTrue);
      expect(leader.canDeleteProjects, isFalse);
      expect(leader.canReviewStandups, isTrue);
      expect(leader.canLogStandup, isTrue);
      expect(leader.canManageProfiles, isFalse);
    });

    test('Specialist has personal pipeline focus and daily standup logging', () {
      final specialist = AuthUser(
        id: 15,
        name: 'Developer',
        email: 'dev@fbinternationalbd.com',
        avatar: '',
        role: 'member',
        roleLabel: 'Specialist',
        team: 'WpTitans',
        isAdmin: false,
        isLeader: false,
        badgeId: '#DEV-15',
        callSign: 'SpeedCoder',
        professionalTitle: 'WordPress Specialist',
      );

      expect(specialist.isExecutive, isFalse);
      expect(specialist.isOperationsManager, isFalse);
      expect(specialist.isSquadLeader, isFalse);
      expect(specialist.isSpecialist, isTrue);
      expect(specialist.isViewOnly, isFalse);
      expect(specialist.canAddProjects, isFalse);
      expect(specialist.canEditProjects, isFalse);
      expect(specialist.canReviewStandups, isFalse);
      expect(specialist.canLogStandup, isTrue);
    });
  });

  group('Mobile Push Notifications Rule Engine Tests', () {
    test('Notification model parses 4 business rules accurately', () {
      final notif1 = AppNotification.fromJson({
        'id': 'notif_assign_101',
        'user_id': 15,
        'project_id': 101,
        'type': 'new_assignment',
        'title': 'New Project Assigned: #FO829141',
        'message': 'You have been assigned to order #FO829141 (\$320).',
        'is_read': false,
        'created_at': '2026-10-31 12:00:00',
        'timestamp': 1793444000,
      });

      expect(notif1.type, equals('new_assignment'));
      expect(notif1.projectId, equals(101));
      expect(notif1.isUrgent, isFalse);
      expect(notif1.isRead, isFalse);

      final notif2 = AppNotification.fromJson({
        'id': 'notif_warn_101',
        'user_id': 15,
        'project_id': 101,
        'type': 'deadline_warning_3d',
        'title': 'Urgent: Deadline < 3 Days Remaining',
        'message': 'Order #FO829141 delivery deadline is in 48 hours!',
        'is_read': false,
        'created_at': '2026-10-31 14:00:00',
        'timestamp': 1793445000,
      });

      expect(notif2.type, equals('deadline_warning_3d'));
      expect(notif2.isUrgent, isTrue);

      final notif3 = AppNotification.fromJson({
        'id': 'notif_prompt_101',
        'user_id': 15,
        'project_id': 101,
        'type': 'progress_prompt_2d',
        'title': 'Standup & Progress Update Required',
        'message': '2 days have passed since order #FO829141 assignment. Please log today\'s progress update.',
        'is_read': false,
        'created_at': '2026-10-31 16:00:00',
        'timestamp': 1793446000,
      });

      expect(notif3.type, equals('progress_prompt_2d'));
      expect(notif3.isUrgent, isFalse);

      final notif4 = AppNotification.fromJson({
        'id': 'notif_ext_101',
        'user_id': 1,
        'project_id': 101,
        'type': 'extension_advisory_3d',
        'title': 'Deadline Extension Advisory: Order #FO829141',
        'message': 'Project #FO829141 has < 3 days remaining. Please review progress with squad and coordinate extension with buyer if needed.',
        'is_read': false,
        'created_at': '2026-10-31 17:00:00',
        'timestamp': 1793447000,
      });

      expect(notif4.type, equals('extension_advisory_3d'));
      expect(notif4.isUrgent, isTrue);
    });
  });
}
