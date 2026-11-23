import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_team_app/models/leaderboard.dart';
import 'package:flutter_team_app/models/project.dart';
import 'package:flutter_team_app/models/monthly_report.dart';

void main() {
  group('Leaderboard Models Test Suite', () {
    test('LeaderboardItem parses aliases and delivery revenue correctly', () {
      final json = {
        'user_id': 1086,
        'member_name': 'Sarah Connor',
        'call_sign': 'Terminator Hunter',
        'team_name': 'WpHorizon',
        'member_custom_id': '#1086',
        'net_delivered_revenue': 1450.0,
        'completed_orders': 8,
        'performance_score': 94,
        'on_time_rate': 100.0,
        'client_rating': 5.0,
        'tips': 120.0,
      };

      final item = LeaderboardItem.fromJson(json, 1);
      expect(item.userId, 1086);
      expect(item.name, 'Sarah Connor');
      expect(item.team, 'WpHorizon');
      expect(item.customId, '#1086');
      expect(item.netRevenue, 1450.0);
      expect(item.completedOrders, 8);
      expect(item.score, 94.0);
      expect(item.tips, 120.0);
      expect(item.clientRating, 5.0);
    });

    test('LeaderboardData handles map methodology without throwing', () {
      final dataJson = {
        'period': 'monthly',
        'period_key': '2026-11',
        'period_label': 'November 2026',
        'selected_team': 'all',
        'sort_by': 'revenue',
        'podium': [
          {
            'user_id': 1,
            'name': 'Top Specialist',
            'net_delivered_revenue': 2000.0,
            'completed_orders': 10,
            'performance_score': 98,
          }
        ],
        'rankings': [
          {
            'user_id': 1,
            'name': 'Top Specialist',
            'net_delivered_revenue': 2000.0,
            'completed_orders': 10,
            'performance_score': 98,
          }
        ],
        'methodology': {
          'scale': '0 to 100 points',
          'pillars': [
            {'name': 'Delivered Revenue Volume', 'weight': '40%', 'formula': 'Member / Max * 40'},
            {'name': 'Client Satisfaction', 'weight': '30%', 'formula': 'Rating / 5 * 20'},
          ],
          'neutral_rating_policy': 'Baseline 12 applied',
        }
      };

      final data = LeaderboardData.fromJson(dataJson);
      expect(data.podium.length, 1);
      expect(data.podium.first.name, 'Top Specialist');
      expect(data.methodology, contains('Delivered Revenue Volume'));
      expect(data.sortBy, 'revenue');
    });
  });

  group('Projects Models & Features Test Suite', () {
    test('ProjectItem correctly parses combined orders and split allocations', () {
      final json = {
        'id': 342,
        'order_id': '#FO-342',
        'client_name': 'Apex Holdings',
        'title': 'Enterprise WooCommerce Portal',
        'gross_order_value': 2500.0,
        'net_delivery_value': 1200.0,
        'order_total_value': 2500.0,
        'is_combined': true,
        'order_type': 'combined',
        'combined': {
          'is_combined': true,
          'order_total_value': 2500.0,
          'my_share': 1200.0,
          'my_share_percent': 48.0,
          'others_total': 1300.0,
          'splits': [
            {
              'id': 1,
              'party_label': 'WpTitans (Design)',
              'skill': 'UI/UX & Figma',
              'share_net': 800.0,
              'note': 'Design handoff completed',
            },
            {
              'id': 2,
              'party_label': 'WpNexus (SEO)',
              'skill': 'Technical SEO',
              'share_net': 500.0,
              'note': 'Schema markup',
            }
          ]
        },
        'delivery_date': '2026-11-15',
        'status': 'wip',
        'progress_percent': 65,
        'team_name': 'WpHorizon',
        'assignee_shares': [
          {
            'user_id': 1086,
            'name': 'Sarah Connor',
            'split_percentage': 60.0,
          },
          {
            'user_id': 1087,
            'name': 'John Connor',
            'split_percentage': 40.0,
          }
        ]
      };

      final project = ProjectItem.fromJson(json);
      expect(project.isCombined, isTrue);
      expect(project.orderTotalValue, 2500.0);
      expect(project.netValue, 1200.0);
      expect(project.splits.length, 2);
      expect(project.splits[0].partyLabel, 'WpTitans (Design)');
      expect(project.splits[0].shareNet, 800.0);
      expect(project.assignees.length, 2);
      expect(project.assignees[0].splitPercent, 60.0);
      expect(project.assignees[1].splitPercent, 40.0);
    });

    test('ProjectItem correctly parses subscription orders and client reviews', () {
      final json = {
        'id': 415,
        'order_id': '#FO-415',
        'client_name': 'Blue Horizon Corp',
        'title': 'Monthly WordPress Maintenance',
        'order_value': 500.0,
        'net_value': 400.0,
        'order_type': 'subscription',
        'subscription_status': 'active',
        'subscription_term_days': 30,
        'subscription_next_date': '2026-12-01',
        'client_reviewed': 'yes',
        'star_rating': 5,
        'review_feedback': 'Exceptional speed and attention to detail!',
        'delivery_date': '2026-11-20',
        'status': 'delivery_accepted',
        'progress_percent': 100,
        'team': 'WpCraft',
      };

      final project = ProjectItem.fromJson(json);
      expect(project.orderType, 'subscription');
      expect(project.subscriptionStatus, 'active');
      expect(project.subscriptionTermDays, 30);
      expect(project.subscriptionNextDate, '2026-12-01');
      expect(project.clientReviewed, 'yes');
      expect(project.starRating, 5);
      expect(project.reviewFeedback, contains('Exceptional speed'));
      expect(project.isCompleted, isTrue);
      expect(project.deliveredWithoutExtension, isTrue);
    });

    test('ProjectItem enforces automatic On-Time logic from extension_count and completion status', () {
      final wipNoExt = ProjectItem.fromJson({
        'id': 501,
        'order_id': '#FO-501',
        'client_name': 'Client A',
        'title': 'Active WIP Order',
        'status': 'wip',
        'extension_count': 0,
        'delivered_without_extension': 1, // Even if stale server/db flag is 1, WIP is not On-Time yet
        'team': 'WpCraft',
      });
      expect(wipNoExt.deliveredWithoutExtension, isFalse);

      final completedWithExt = ProjectItem.fromJson({
        'id': 502,
        'order_id': '#FO-502',
        'client_name': 'Client B',
        'title': 'Completed With Extension',
        'status': 'delivery_accepted',
        'extension_count': 1,
        'delivered_without_extension': 1, // Extension present -> NEVER On-Time
        'team': 'WpCraft',
      });
      expect(completedWithExt.deliveredWithoutExtension, isFalse);

      final submittedNoExt = ProjectItem.fromJson({
        'id': 503,
        'order_id': '#FO-503',
        'client_name': 'Client C',
        'title': 'Submitted Pending Acceptance',
        'status': 'delivery_submitted',
        'extension_count': 0,
        'delivered_without_extension': 0,
        'team': 'WpCraft',
      });
      expect(submittedNoExt.deliveredWithoutExtension, isFalse);

      final completedNoExt = ProjectItem.fromJson({
        'id': 504,
        'order_id': '#FO-504',
        'client_name': 'Client D',
        'title': 'Accepted Without Extension',
        'status': 'completed',
        'extension_count': 0,
        'delivered_without_extension': 0, // Completed + 0 extensions -> auto On-Time
        'team': 'WpCraft',
      });
      expect(completedNoExt.deliveredWithoutExtension, isTrue);
    });
  });

  group('Monthly Reports Single-Source Net Realization Test Suite', () {
    test('MonthlyReportRow does NOT double-deduct 80% from total_delivery', () {
      final rowJson = {
        'user_id': 1086,
        'member_name': 'Sarah Connor',
        'team_name': 'WpHorizon',
        'total_delivery': 1000.0, // Already 80% net realized in database!
        'total_wip': 500.0,
        'tips': 50.0,
        'total_completed': 5,
      };

      final row = MonthlyReportRow.fromJson(rowJson);
      // Net must equal exactly 1000.0, NOT 800.0 (which would be double-deducted)
      expect(row.deliveredNet, 1000.0);
      // Gross must be 1250.0 (1000 / 0.8)
      expect(row.deliveredGross, 1250.0);
      // Realized net with tips must be 1050.0
      expect(row.totalRealizedNet, 1050.0);
    });

    test('MonthlyReportData correctly sums net and gross totals', () {
      final reportJson = {
        'reporting_month': 'September_2026',
        'is_historical_archive': true,
        'totals': {
          'total_delivery': 40000.0,
          'total_delivery_gross': 50000.0,
          'tips': 1200.0,
          'deliveries': 85,
        },
        'rows': [
          {
            'user_id': 1086,
            'member_name': 'Sarah Connor',
            'team_name': 'WpHorizon',
            'total_delivery': 4000.0,
            'total_completed': 8,
          }
        ]
      };

      final data = MonthlyReportData.fromJson(reportJson);
      expect(data.isHistoricalArchive, isTrue);
      expect(data.totalNet, 40000.0);
      expect(data.totalGross, 50000.0);
      expect(data.totalTips, 1200.0);
      expect(data.totalDeliveries, 85);
      expect(data.rows.length, 1);
      expect(data.rows.first.deliveredNet, 4000.0);
    });
  });
}

