import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_team_app/models/time_tracker.dart';
import 'package:flutter_team_app/providers/time_tracker_provider.dart';

void main() {
  group('Time Tracker Models & Parity Test Suite', () {
    test('ActiveTrackingSession parses server /active payload keys accurately', () {
      final json = {
        'session_id': 42,
        'project_id': 15,
        'order_id': '#FO3233BB2AD07',
        'client_name': 'mariansophia',
        'profile_name': 'web_symbol',
        'session_type': 'regular',
        'start_time': '2026-11-10 13:10:00',
        'start_timestamp': 1762780200000,
        'elapsed_seconds': 52,
        'snapshots_count': 1,
        'total_mouse_clicks': 142,
        'total_keyboard_clicks': 385,
        'status': 'active',
      };

      final session = ActiveTrackingSession.fromJson(json);
      expect(session.sessionId, 42);
      expect(session.projectId, 15);
      expect(session.orderId, '#FO3233BB2AD07');
      expect(session.clientName, 'mariansophia');
      expect(session.profileName, 'web_symbol');
      expect(session.startTime, '2026-11-10 13:10:00');
      expect(session.elapsedSeconds, 52);
      expect(session.snapshotsCount, 1);
      expect(session.totalMouseClicks, 142);
      expect(session.totalKeyboardClicks, 385);
      expect(session.isExtraTime, isFalse);
      expect(session.liveElapsedSeconds, greaterThanOrEqualTo(52));
    });

    test('DailyTimeSummaryResponse parses date-wise records, daily totals, and summary', () {
      final json = {
        'summary': {
          'total_seconds': 9198,
          'total_formatted': '2h 33m',
          'regular_seconds': 9000,
          'regular_formatted': '2h 30m',
          'extra_seconds': 198,
          'extra_formatted': '0h 03m',
          'days_count': 1,
          'sessions_count': 2,
          'snapshots_count': 6,
        },
        'daily_totals': [
          {
            'date': '2026-11-10',
            'date_formatted': 'Tue, Nov 10, 2026',
            'is_today': true,
            'specialists_count': 1,
            'sessions_count': 2,
            'snapshots_count': 6,
            'regular_seconds': 9000,
            'regular_formatted': '2h 30m',
            'extra_seconds': 198,
            'extra_formatted': '0h 03m',
            'total_seconds': 9198,
            'total_formatted': '2h 33m',
          },
        ],
        'records': [
          {
            'date': '2026-11-10',
            'date_formatted': 'Tue, Nov 10, 2026',
            'is_today': true,
            'user_id': 1,
            'specialist_name': 'Ops Lead',
            'avatar': '',
            'team_name': 'WpCraft',
            'regular_seconds': 9000,
            'regular_formatted': '2h 30m',
            'extra_seconds': 198,
            'extra_formatted': '0h 03m',
            'total_seconds': 9198,
            'total_formatted': '2h 33m',
            'total_hours_decimal': 2.56,
            'sessions_count': 2,
            'active_count': 0,
            'snapshots_count': 6,
            'orders': [
              {
                'project_id': 15,
                'order_id': '#FO3233BB2AD07',
                'client_name': 'mariansophia',
                'seconds': 9198,
                'time_formatted': '2h 33m',
              }
            ],
          }
        ],
        'specialists': [
          {'user_id': 1, 'name': 'Ops Lead', 'team': 'WpCraft'}
        ],
      };

      final parsed = DailyTimeSummaryResponse.fromJson(json);
      expect(parsed.summary.totalFormatted, '2h 33m');
      expect(parsed.summary.daysCount, 1);
      expect(parsed.dailyTotals.length, 1);
      expect(parsed.dailyTotals.first.isToday, isTrue);
      expect(parsed.records.length, 1);
      expect(parsed.records.first.orders.length, 1);
      expect(parsed.records.first.orders.first.orderId, '#FO3233BB2AD07');
      expect(parsed.specialists.first.name, 'Ops Lead');
    });

    test('TimeSnapshotItem parses live snapshot API fields cleanly', () {
      final snap = TimeSnapshotItem.fromJson({
        'id': 9,
        'session_id': 26,
        'user_id': 12,
        'specialist_name': 'Jane Doe',
        'project_id': 8,
        'order_id': '#FO999',
        'client_name': 'acme',
        'team_name': 'WpTitans',
        'session_type': 'extra_time',
        'notes': 'Verified homepage deploy',
        'url': 'https://team.fbinternationalbd.com/wp-content/uploads/snap.jpg',
        'elapsed_seconds': 600,
        'elapsed_label': '+10m',
        'mouse_clicks': 78,
        'keyboard_clicks': 214,
        'captured_at': '2026-11-10 14:20:00',
        'captured_at_formatted': 'Nov 10, 2026 2:20 PM',
      });

      expect(snap.id, 9);
      expect(snap.sessionId, 26);
      expect(snap.specialistName, 'Jane Doe');
      expect(snap.auditNotes, 'Verified homepage deploy');
      expect(snap.elapsedLabel, '+10m');
      expect(snap.mouseClicks, 78);
      expect(snap.keyboardClicks, 214);
      expect(snap.formattedTime, 'Nov 10, 2026 2:20 PM');
    });

    test('TimeTrackerProvider formats clock & compact strings cleanly', () {
      expect(TimeTrackerProvider.formatHmsClock(3661), '01:01:01');
      expect(TimeTrackerProvider.formatHmsCompact(52), '0m 52s');
      expect(TimeTrackerProvider.formatHmsCompact(9198), '2h 33m');
    });
  });
}

