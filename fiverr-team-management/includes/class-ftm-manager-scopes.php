<?php
/**
 * Which manager was responsible for which team in which months.
 *
 * Team results belong to the manager who ran the team at that time, so a manager's executive totals add up
 * only the (team, month) pairs they managed. Member, team and leaderboard figures are not affected.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Manager_Scopes {

    const SEED_OPTION = 'ftm_manager_scopes_seed';
    const SEED_VERSION = '1';

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'ftm_manager_scopes';
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $t = self::table();
        dbDelta("CREATE TABLE $t (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            manager_user_id BIGINT(20) UNSIGNED NOT NULL,
            team_name VARCHAR(100) NOT NULL,
            start_month CHAR(7) NOT NULL,
            end_month CHAR(7) NULL,
            note VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY manager_user_id (manager_user_id)
        ) " . $wpdb->get_charset_collate() . ";");
        self::seed();
    }

    /**
     * Starting point, editable through the API: the Operations Manager runs Craft and Titans throughout and Nexus
     * except while the Deputy Manager ran it (April-July 2026). Horizon is its own team from August, outside both.
     */
    private static function seed() {
        global $wpdb;
        if (get_option(self::SEED_OPTION) === self::SEED_VERSION) {
            return;
        }
        $pranto = self::user_by_login('shahriar_pranto') ?: 1;
        $arafat = self::user_by_login('arafat_sarker');
        $t = self::table();
        if ((int)$wpdb->get_var("SELECT COUNT(*) FROM $t") === 0) {
            $rows = [
                [$pranto, 'WpCraft', '2026-01', null, ''],
                [$pranto, 'WpTitans', '2026-01', null, ''],
                [$pranto, 'WpNexus', '2026-01', '2026-03', ''],
                [$pranto, 'WpNexus', '2026-08', null, 'Back under the Operations Manager from August'],
            ];
            if ($arafat) {
                $rows[] = [$arafat, 'WpNexus', '2026-04', '2026-07', 'Ran WpNexus April-July 2026'];
                $rows[] = [$arafat, 'WpHorizon', '2026-08', null, 'Runs WpHorizon from August 2026, including everything it does from here on'];
            }
            foreach ($rows as [$uid, $team, $from, $to, $note]) {
                $wpdb->insert($t, ['manager_user_id' => (int)$uid, 'team_name' => $team, 'start_month' => $from, 'end_month' => $to, 'note' => $note]);
            }
        }
        update_option(self::SEED_OPTION, self::SEED_VERSION);
    }

    private static function user_by_login($login) {
        $u = get_user_by('login', $login);
        return $u ? (int)$u->ID : 0;
    }

    public static function scopes_for($manager_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, team_name, start_month, end_month, note FROM " . self::table() . " WHERE manager_user_id = %d ORDER BY team_name, start_month",
            (int)$manager_id
        ), ARRAY_A) ?: [];
    }

    private static function covers($scope, $team, $ym) {
        return strcasecmp($scope['team_name'], $team) === 0
            && $ym >= $scope['start_month']
            && ($scope['end_month'] === null || $scope['end_month'] === '' || $ym <= $scope['end_month']);
    }

    /** 'January_2026' / 'july_2026' -> '2026-01' */
    private static function archive_month_to_ym($label) {
        $ts = strtotime('1 ' . str_replace('_', ' ', $label));
        return $ts ? date('Y-m', $ts) : '';
    }

    /**
     * Totals for the (team, month) pairs this manager ran. Returns null when the manager has no scopes
     * (callers then keep the old whole-agency behaviour).
     */
    public static function totals($manager_id) {
        global $wpdb;
        $scopes = self::scopes_for($manager_id);
        if (!$scopes) {
            return null;
        }
        $out = ['archive_delivery' => 0.0, 'archive_completed' => 0, 'archive_stars5' => 0, 'archive_ontime' => 0,
                'live_completed' => 0, 'live_in_progress' => 0, 'live_revisions' => 0, 'live_delivered_net' => 0.0, 'live_stars' => 0, 'live_ontime' => 0,
                'total_projects_live' => 0];

        foreach ($wpdb->get_results("SELECT reporting_month, team_name, total_delivery, total_completed, stars_5, delivered_without_extension FROM {$wpdb->prefix}ftm_monthly_archive") as $r) {
            $ym = self::archive_month_to_ym($r->reporting_month);
            foreach ($scopes as $sc) {
                if (self::covers($sc, $r->team_name, $ym)) {
                    $out['archive_delivery'] += (float)$r->total_delivery;
                    $out['archive_completed'] += (int)$r->total_completed;
                    $out['archive_stars5'] += (int)$r->stars_5;
                    $out['archive_ontime'] += (int)$r->delivered_without_extension;
                    break;
                }
            }
        }

        // Live projects belong to the month they were delivered in (open work: this month).
        $parts = [];
        $params = [];
        foreach ($scopes as $sc) {
            $part = "(team_name = %s AND DATE_FORMAT(COALESCE(delivered_date, NOW()), '%%Y-%%m') >= %s";
            $params[] = $sc['team_name'];
            $params[] = $sc['start_month'];
            if (!empty($sc['end_month'])) {
                $part .= " AND DATE_FORMAT(COALESCE(delivered_date, NOW()), '%%Y-%%m') <= %s";
                $params[] = $sc['end_month'];
            }
            $parts[] = $part . ')';
        }
        $sql = "SELECT
                COUNT(*) AS total_projects,
                SUM(CASE WHEN status IN ('delivery_accepted','completed') THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status IN ('wip','nra','delivery_submitted','submitted') THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN status IN ('revision','in_revision','revision_in_progress') THEN 1 ELSE 0 END) AS revisions,
                SUM(CASE WHEN status IN ('delivery_accepted','completed') THEN (net_delivery_value + tip_amount * 0.8) ELSE 0 END) AS delivered_net,
                SUM(CASE WHEN ROUND(star_rating) = 5 THEN 1 ELSE 0 END) AS stars,
                SUM(CASE WHEN status IN ('delivery_accepted','completed') AND extension_count = 0 THEN 1 ELSE 0 END) AS ontime
            FROM {$wpdb->prefix}ftm_projects WHERE " . implode(' OR ', $parts);
        $live = $wpdb->get_row($wpdb->prepare($sql, $params));
        if ($live) {
            $out['live_completed'] = (int)$live->completed;
            $out['live_in_progress'] = (int)$live->in_progress;
            $out['live_revisions'] = (int)$live->revisions;
            $out['live_delivered_net'] = (float)$live->delivered_net;
            $out['live_stars'] = (int)$live->stars;
            $out['live_ontime'] = (int)$live->ontime;
            $out['total_projects_live'] = (int)$live->total_projects;
        }
        return $out;
    }

    /**
     * Per-team figures limited to the months this manager ran each team.
     * @return array team => [volume, completed, ontime, active_projects, periods, current]
     */
    public static function per_team($manager_id) {
        global $wpdb;
        $scopes = self::scopes_for($manager_id);
        $out = [];
        $now_ym = current_time('Y-m');
        foreach ($scopes as $sc) {
            $team = $sc['team_name'];
            if (!isset($out[$team])) {
                $out[$team] = ['volume' => 0.0, 'completed' => 0, 'ontime' => 0, 'active_projects' => 0, 'periods' => [], 'current' => false];
            }
            $to = empty($sc['end_month']) ? null : $sc['end_month'];
            $out[$team]['periods'][] = date('M Y', strtotime($sc['start_month'] . '-01')) . ' - ' . ($to ? date('M Y', strtotime($to . '-01')) : 'now');
            if ($to === null || $to >= $now_ym) {
                $out[$team]['current'] = true;
            }
            // live delivered volume, completed count, and on-time count (0 extensions) in the window (strictly accepted or completed)
            $sql = "SELECT
                    COALESCE(SUM(net_delivery_value),0) AS vol,
                    COUNT(*) AS completed,
                    SUM(CASE WHEN extension_count = 0 THEN 1 ELSE 0 END) AS ontime
                FROM {$wpdb->prefix}ftm_projects
                WHERE team_name = %s AND status IN ('delivery_accepted','completed') AND DATE_FORMAT(COALESCE(delivered_date, delivery_date), '%%Y-%%m') >= %s";
            $params = [$team, $sc['start_month']];
            if ($to) {
                $sql .= " AND DATE_FORMAT(COALESCE(delivered_date, delivery_date), '%%Y-%%m') <= %s";
                $params[] = $to;
            }
            $live_row = $wpdb->get_row($wpdb->prepare($sql, $params));
            if ($live_row) {
                $out[$team]['volume'] += (float)$live_row->vol;
                $out[$team]['completed'] += (int)$live_row->completed;
                $out[$team]['ontime'] += (int)$live_row->ontime;
            }
            // open work counts only while the manager runs the team
            if ($sc['start_month'] <= $now_ym && ($to === null || $to >= $now_ym)) {
                $out[$team]['active_projects'] += (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ftm_projects WHERE team_name = %s AND status IN ('nra','wip','revision','delivery_submitted','submitted')", $team
                ));
            }
        }
        foreach ($wpdb->get_results("SELECT reporting_month, team_name, total_delivery, total_completed, delivered_without_extension FROM {$wpdb->prefix}ftm_monthly_archive") as $r) {
            $ym = self::archive_month_to_ym($r->reporting_month);
            foreach ($scopes as $sc) {
                if (self::covers($sc, $r->team_name, $ym)) {
                    $key = $sc['team_name'];
                    $out[$key]['volume'] += (float)$r->total_delivery;
                    $out[$key]['completed'] += (int)$r->total_completed;
                    $out[$key]['ontime'] += (int)$r->delivered_without_extension;
                    break;
                }
            }
        }
        return $out;
    }

    /** Readable lines for the profile page. */
    public static function describe($manager_id) {
        $rows = [];
        foreach (self::scopes_for($manager_id) as $sc) {
            $from = date('M Y', strtotime($sc['start_month'] . '-01'));
            $to = empty($sc['end_month']) ? 'now' : date('M Y', strtotime($sc['end_month'] . '-01'));
            $rows[] = ['team' => $sc['team_name'], 'from' => $from, 'to' => $to, 'note' => $sc['note']];
        }
        return $rows;
    }
}
